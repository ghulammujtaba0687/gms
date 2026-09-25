<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipFreeze;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Role;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $manager;

    protected User $receptionist;

    protected User $trainer;

    protected Branch $branch1;

    protected Branch $branch2;

    protected MembershipPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['display_name' => 'Gym Owner']);
        $managerRole = Role::firstOrCreate(['name' => 'manager'], ['display_name' => 'Manager']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist'], ['display_name' => 'Receptionist']);
        $trainerRole = Role::firstOrCreate(['name' => 'trainer'], ['display_name' => 'Trainer']);

        $this->branch1 = Branch::create(['name' => 'DHA Branch', 'code' => 'DHA', 'is_active' => true]);
        $this->branch2 = Branch::create(['name' => 'Gulberg Branch', 'code' => 'GLB', 'is_active' => true]);

        $this->plan = MembershipPlan::create([
            'branch_id' => null,
            'name' => 'Monthly Fitness Plan',
            'code' => 'PLAN-MON',
            'duration_days' => 30,
            'price' => 5000.00,
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create(['status' => 'active']);
        $this->owner->roles()->attach($ownerRole->id);

        $this->manager = User::factory()->create(['status' => 'active']);
        $this->manager->roles()->attach($managerRole->id);
        $this->manager->branches()->attach($this->branch1->id);

        $this->receptionist = User::factory()->create(['status' => 'active']);
        $this->receptionist->roles()->attach($receptionistRole->id);
        $this->receptionist->branches()->attach($this->branch1->id);

        $this->trainer = User::factory()->create(['status' => 'active']);
        $this->trainer->roles()->attach($trainerRole->id);
        $this->trainer->branches()->attach($this->branch1->id);
    }

    public function test_idempotency_prevents_duplicate_notifications(): void
    {
        $service = app(NotificationService::class);

        $data = [
            'branch_id' => $this->branch1->id,
            'type' => 'expiry_reminder',
            'title' => 'Test Expiry',
            'message' => 'Test message',
            'idempotency_key' => 'unique_key_123',
        ];

        $n1 = $service->createNotification($data);
        $n2 = $service->createNotification($data);

        $this->assertEquals($n1->id, $n2->id);
        $this->assertEquals(1, SystemNotification::where('idempotency_key', 'unique_key_123')->count());
    }

    public function test_expiry_and_expired_reminders_generation_via_command(): void
    {
        $member = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'DHA-M-00001',
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'cnic' => '35202-1234567-1',
            'phone' => '03001234567',
            'gender' => 'male',
            'status' => 'active',
        ]);

        // Membership expiring in 7 days
        Membership::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $member->id,
            'membership_plan_id' => $this->plan->id,
            'plan_name_snapshot' => $this->plan->name,
            'plan_code_snapshot' => $this->plan->code,
            'plan_price_snapshot' => $this->plan->price,
            'duration_days_snapshot' => 30,
            'start_date' => Carbon::today()->subDays(23),
            'end_date' => Carbon::today()->addDays(7),
            'status' => 'active',
        ]);

        // Membership expired today
        Membership::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $member->id,
            'membership_plan_id' => $this->plan->id,
            'plan_name_snapshot' => $this->plan->name,
            'plan_code_snapshot' => $this->plan->code,
            'plan_price_snapshot' => $this->plan->price,
            'duration_days_snapshot' => 30,
            'start_date' => Carbon::today()->subDays(30),
            'end_date' => Carbon::today(),
            'status' => 'expired',
        ]);

        Artisan::call('gms:send-reminders');

        $this->assertDatabaseHas('system_notifications', [
            'type' => 'expiry_reminder',
            'branch_id' => $this->branch1->id,
        ]);

        $this->assertDatabaseHas('system_notifications', [
            'type' => 'expired',
            'branch_id' => $this->branch1->id,
        ]);
    }

    public function test_dues_and_freeze_reminders_generation(): void
    {
        $member = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'DHA-M-00002',
            'first_name' => 'Usman',
            'last_name' => 'Tariq',
            'cnic' => '35202-1234567-2',
            'phone' => '03001234568',
            'gender' => 'male',
            'status' => 'active',
        ]);

        Payment::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $member->id,
            'payment_code' => 'DHA-PAY-00001',
            'amount_due' => 5000,
            'amount_paid' => 3000,
            'discount_amount' => 0,
            'remaining_balance' => 2000,
            'payment_method' => 'cash',
            'payment_date' => Carbon::today(),
            'status' => 'partial',
            'recorded_by' => $this->manager->id,
        ]);

        $ms = Membership::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $member->id,
            'membership_plan_id' => $this->plan->id,
            'plan_name_snapshot' => $this->plan->name,
            'plan_code_snapshot' => $this->plan->code,
            'plan_price_snapshot' => $this->plan->price,
            'duration_days_snapshot' => 30,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(30),
            'status' => 'active',
        ]);

        MembershipFreeze::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $member->id,
            'membership_id' => $ms->id,
            'requested_by' => $this->manager->id,
            'approved_by' => $this->owner->id,
            'freeze_start_date' => Carbon::today()->subDays(5),
            'freeze_end_date' => Carbon::tomorrow(),
            'frozen_days' => 6,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        Artisan::call('gms:send-reminders');

        $this->assertDatabaseHas('system_notifications', ['type' => 'dues_reminder']);
        $this->assertDatabaseHas('system_notifications', ['type' => 'freeze_ending']);
    }

    public function test_payment_and_freeze_creation_triggers_instant_notifications(): void
    {
        $member = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'DHA-M-00003',
            'first_name' => 'Sara',
            'last_name' => 'Ahmed',
            'cnic' => '35202-1234567-3',
            'phone' => '03001234569',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post(route('payments.store'), [
                'member_id' => $member->id,
                'amount_due' => 5000,
                'amount_paid' => 5000,
                'payment_method' => 'cash',
                'payment_date' => Carbon::today()->toDateString(),
            ]);

        $this->assertDatabaseHas('system_notifications', [
            'type' => 'payment_received',
            'branch_id' => $this->branch1->id,
        ]);
    }

    public function test_broadcast_notifications_and_unread_count(): void
    {
        SystemNotification::create([
            'branch_id' => $this->branch1->id,
            'user_id' => null, // Broadcast
            'type' => 'payment_received',
            'title' => 'Broadcast Test',
            'message' => 'Broadcast message',
            'idempotency_key' => 'broadcast_1',
        ]);

        $service = app(NotificationService::class);
        $count = $service->getUnreadCount($this->manager, $this->branch1->id);

        $this->assertEquals(1, $count);
    }

    public function test_mark_as_read_and_mark_all_read(): void
    {
        $n1 = SystemNotification::create([
            'branch_id' => $this->branch1->id,
            'user_id' => null,
            'type' => 'dues_reminder',
            'title' => 'Dues 1',
            'message' => 'Msg 1',
            'idempotency_key' => 'key_1',
        ]);

        $n2 = SystemNotification::create([
            'branch_id' => $this->branch1->id,
            'user_id' => null,
            'type' => 'dues_reminder',
            'title' => 'Dues 2',
            'message' => 'Msg 2',
            'idempotency_key' => 'key_2',
        ]);

        $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post(route('notifications.read', $n1->id));

        $this->assertNotNull($n1->fresh()->read_at);
        $this->assertNull($n2->fresh()->read_at);

        $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post(route('notifications.read-all'));

        $this->assertNotNull($n2->fresh()->read_at);
    }

    public function test_retention_60_day_read_cleanup_retains_unread(): void
    {
        $oldRead = SystemNotification::create([
            'branch_id' => $this->branch1->id,
            'type' => 'expired',
            'title' => 'Old Read',
            'message' => 'Old Read Msg',
            'idempotency_key' => 'old_read',
            'read_at' => Carbon::now()->subDays(61),
        ]);

        $oldUnread = SystemNotification::create([
            'branch_id' => $this->branch1->id,
            'type' => 'expired',
            'title' => 'Old Unread',
            'message' => 'Old Unread Msg',
            'idempotency_key' => 'old_unread',
            'read_at' => null,
        ]);

        $service = app(NotificationService::class);
        $service->cleanUpReadNotifications(60);

        $this->assertDatabaseMissing('system_notifications', ['id' => $oldRead->id]);
        $this->assertDatabaseHas('system_notifications', ['id' => $oldUnread->id]);
    }

    public function test_cross_branch_idor_protection_for_manager(): void
    {
        $otherBranchNotification = SystemNotification::create([
            'branch_id' => $this->branch2->id,
            'type' => 'expired',
            'title' => 'Branch 2 Notification',
            'message' => 'Branch 2 Msg',
            'idempotency_key' => 'branch2_key',
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post(route('notifications.read', $otherBranchNotification->id));

        $response->assertStatus(403);
    }

    public function test_settings_disable_behavior(): void
    {
        SettingService::set('enable_expiry_notifications', false, $this->branch1->id, 'operational');

        $member = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'DHA-M-00004',
            'first_name' => 'Zain',
            'last_name' => 'Abbas',
            'cnic' => '35202-1234567-4',
            'phone' => '03001234570',
            'gender' => 'male',
            'status' => 'active',
        ]);

        Membership::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $member->id,
            'membership_plan_id' => $this->plan->id,
            'plan_name_snapshot' => $this->plan->name,
            'plan_code_snapshot' => $this->plan->code,
            'plan_price_snapshot' => $this->plan->price,
            'duration_days_snapshot' => 30,
            'start_date' => Carbon::today()->subDays(23),
            'end_date' => Carbon::today()->addDays(7),
            'status' => 'active',
        ]);

        Artisan::call('gms:send-reminders');

        $this->assertDatabaseMissing('system_notifications', [
            'type' => 'expiry_reminder',
            'branch_id' => $this->branch1->id,
        ]);
    }
}
