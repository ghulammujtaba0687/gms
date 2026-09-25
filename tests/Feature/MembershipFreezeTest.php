<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipFreeze;
use App\Models\MembershipPlan;
use App\Models\Role;
use App\Models\User;
use App\Services\MembershipCalculationService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipFreezeTest extends TestCase
{
    use RefreshDatabase;

    protected GymProfile $gym;

    protected Branch $branch1;

    protected Branch $branch2;

    protected User $owner;

    protected User $manager;

    protected User $receptionist;

    protected User $trainer;

    protected Member $member1;

    protected MembershipPlan $planMonthly;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->gym = GymProfile::create(['name' => 'Gym', 'currency' => 'PKR', 'timezone' => 'Asia/Karachi']);
        $this->branch1 = Branch::create(['gym_profile_id' => $this->gym->id, 'code' => 'B1', 'name' => 'Branch 1']);
        $this->branch2 = Branch::create(['gym_profile_id' => $this->gym->id, 'code' => 'B2', 'name' => 'Branch 2']);

        $this->owner = User::factory()->create(['status' => 'active']);
        $this->owner->roles()->attach(Role::where('name', 'owner')->first());

        $this->manager = User::factory()->create(['status' => 'active']);
        $this->manager->roles()->attach(Role::where('name', 'manager')->first());
        $this->manager->branches()->attach($this->branch1);

        $this->receptionist = User::factory()->create(['status' => 'active']);
        $this->receptionist->roles()->attach(Role::where('name', 'receptionist')->first());
        $this->receptionist->branches()->attach($this->branch1);

        $this->trainer = User::factory()->create(['status' => 'active']);
        $this->trainer->roles()->attach(Role::where('name', 'trainer')->first());
        $this->trainer->branches()->attach($this->branch1);

        $this->member1 = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'B1-M-00001',
            'first_name' => 'Ali',
            'last_name' => 'Raza',
            'phone' => '+923001111111',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);

        $this->planMonthly = MembershipPlan::create([
            'branch_id' => $this->branch1->id,
            'code' => 'MONTHLY-01',
            'name' => 'Monthly Plan',
            'price' => 5000.00,
            'duration_type' => 'months',
            'duration_value' => 1,
            'is_active' => true,
        ]);
    }

    public function test_manager_can_create_and_auto_approve_freeze_extending_end_date(): void
    {
        $mCalc = new MembershipCalculationService;
        $membership = $mCalc->assignMembership($this->member1->id, $this->planMonthly->id, '2025-01-01');
        $originalEndDate = $membership->end_date->format('Y-m-d'); // 2025-01-31

        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/membership-freezes', [
                'membership_id' => $membership->id,
                'freeze_start_date' => '2025-01-10',
                'freeze_end_date' => '2025-01-14', // 5 Days inclusive
                'reason' => 'Medical leave',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('membership_freezes', [
            'membership_id' => $membership->id,
            'frozen_days' => 5,
            'status' => 'approved',
        ]);

        $membership->refresh();
        // Original 2025-01-31 + 5 Days = 2025-02-05
        $this->assertEquals('2025-02-05', $membership->end_date->format('Y-m-d'));
    }

    public function test_receptionist_created_freeze_is_pending_and_manager_approves_extending_date(): void
    {
        $mCalc = new MembershipCalculationService;
        $membership = $mCalc->assignMembership($this->member1->id, $this->planMonthly->id, '2025-01-01');

        // Receptionist creates freeze -> Status Pending, End Date NOT extended yet
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/membership-freezes', [
                'membership_id' => $membership->id,
                'freeze_start_date' => '2025-01-10',
                'freeze_end_date' => '2025-01-14',
                'reason' => 'Exam leave',
            ]);

        $response->assertRedirect();
        $freeze = MembershipFreeze::where('membership_id', $membership->id)->first();
        $this->assertEquals('pending', $freeze->status);

        $membership->refresh();
        $this->assertEquals('2025-01-31', $membership->end_date->format('Y-m-d'));

        // Manager Approves Freeze -> Status Approved, End Date extended by 5 Days
        $approveResponse = $this->actingAs($this->manager)
            ->post("/membership-freezes/{$freeze->id}/approve");

        $approveResponse->assertRedirect();
        $freeze->refresh();
        $this->assertEquals('approved', $freeze->status);

        $membership->refresh();
        $this->assertEquals('2025-02-05', $membership->end_date->format('Y-m-d'));
    }

    public function test_active_freeze_blocks_attendance_check_in(): void
    {
        $mCalc = new MembershipCalculationService;
        $membership = $mCalc->assignMembership($this->member1->id, $this->planMonthly->id, Carbon::today()->toDateString());

        // Create Active Freeze for Today
        MembershipFreeze::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $this->member1->id,
            'membership_id' => $membership->id,
            'freeze_start_date' => Carbon::today()->toDateString(),
            'freeze_end_date' => Carbon::today()->addDays(5)->toDateString(),
            'frozen_days' => 6,
            'reason' => 'Travel',
            'status' => 'approved',
        ]);

        // Attempt Check-In -> Blocked with Freeze Error
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', ['member_id' => $this->member1->id]);

        $response->assertSessionHasErrors('freeze');
    }

    public function test_future_scheduled_freeze_allows_check_in_today(): void
    {
        $mCalc = new MembershipCalculationService;
        $membership = $mCalc->assignMembership($this->member1->id, $this->planMonthly->id, Carbon::today()->toDateString());

        // Create Future Freeze (Starts 5 days from now)
        MembershipFreeze::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $this->member1->id,
            'membership_id' => $membership->id,
            'freeze_start_date' => Carbon::today()->addDays(5)->toDateString(),
            'freeze_end_date' => Carbon::today()->addDays(10)->toDateString(),
            'frozen_days' => 6,
            'reason' => 'Upcoming Vacation',
            'status' => 'approved',
        ]);

        // Attempt Check-In Today -> Allowed
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', ['member_id' => $this->member1->id]);

        $response->assertRedirect('/attendances');
    }

    public function test_freeze_cancellation_restores_original_end_date(): void
    {
        $mCalc = new MembershipCalculationService;
        $membership = $mCalc->assignMembership($this->member1->id, $this->planMonthly->id, '2025-01-01');

        $freeze = MembershipFreeze::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $this->member1->id,
            'membership_id' => $membership->id,
            'freeze_start_date' => '2025-01-10',
            'freeze_end_date' => '2025-01-14',
            'frozen_days' => 5,
            'reason' => 'Travel',
            'status' => 'approved',
        ]);

        // Extended end date
        $membership->update(['end_date' => '2025-02-05']);

        // Cancel Freeze
        $response = $this->actingAs($this->manager)
            ->post("/membership-freezes/{$freeze->id}/cancel", [
                'cancellation_reason' => 'Returned early from trip',
            ]);

        $response->assertRedirect();
        $freeze->refresh();
        $this->assertEquals('cancelled', $freeze->status);

        $membership->refresh();
        // Subtracted 5 days back to original 2025-01-31
        $this->assertEquals('2025-01-31', $membership->end_date->format('Y-m-d'));
    }

    public function test_overlapping_freeze_period_is_rejected(): void
    {
        $mCalc = new MembershipCalculationService;
        $membership = $mCalc->assignMembership($this->member1->id, $this->planMonthly->id, '2025-01-01');

        // Create initial freeze (Jan 10 to Jan 15)
        MembershipFreeze::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $this->member1->id,
            'membership_id' => $membership->id,
            'freeze_start_date' => '2025-01-10',
            'freeze_end_date' => '2025-01-15',
            'frozen_days' => 6,
            'reason' => 'First Freeze',
            'status' => 'approved',
        ]);

        // Attempt overlapping freeze (Jan 12 to Jan 18) -> Session Error
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/membership-freezes', [
                'membership_id' => $membership->id,
                'freeze_start_date' => '2025-01-12',
                'freeze_end_date' => '2025-01-18',
                'reason' => 'Overlapping Freeze',
            ]);

        $response->assertSessionHasErrors('freeze_start_date');
    }

    public function test_trainer_has_view_only_access_to_freezes(): void
    {
        $response = $this->actingAs($this->trainer)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/membership-freezes/create');

        $response->assertStatus(403);
    }

    public function test_cross_branch_freeze_idor_protection(): void
    {
        $otherMember = Member::create([
            'branch_id' => $this->branch2->id,
            'member_code' => 'B2-M-00001',
            'first_name' => 'Tariq',
            'last_name' => 'Jamil',
            'phone' => '+923002222222',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);

        $mCalc = new MembershipCalculationService;
        $b2Membership = $mCalc->assignMembership($otherMember->id, $this->planMonthly->id, '2025-01-01');

        // Manager 1 (Branch 1) attempts freeze on Branch 2 Membership -> 403
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/membership-freezes', [
                'membership_id' => $b2Membership->id,
                'freeze_start_date' => '2025-01-10',
                'freeze_end_date' => '2025-01-15',
                'reason' => 'IDOR Test',
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
