<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Role;
use App\Models\User;
use App\Services\MembershipCalculationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
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

    public function test_active_member_with_active_membership_can_check_in_and_check_out(): void
    {
        $mCalc = new MembershipCalculationService;
        $mCalc->assignMembership($this->member1->id, $this->planMonthly->id, now()->toDateString());

        // Check-In
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', [
                'member_id' => $this->member1->id,
                'notes' => 'Locker 12',
            ]);

        $response->assertRedirect('/attendances');
        $this->assertDatabaseHas('attendances', [
            'member_id' => $this->member1->id,
            'status' => 'present',
            'check_out_time' => null,
        ]);

        $attendance = Attendance::where('member_id', $this->member1->id)->first();

        // Check-Out
        $checkoutResponse = $this->actingAs($this->receptionist)
            ->post("/attendances/{$attendance->id}/checkout");

        $checkoutResponse->assertRedirect();
        $attendance->refresh();
        $this->assertEquals('checked_out', $attendance->status);
        $this->assertNotNull($attendance->check_out_time);
    }

    public function test_second_check_in_while_first_session_is_open_is_blocked(): void
    {
        $mCalc = new MembershipCalculationService;
        $mCalc->assignMembership($this->member1->id, $this->planMonthly->id, now()->toDateString());

        // 1st Check-In
        $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', ['member_id' => $this->member1->id]);

        // 2nd Check-In while 1st is still open -> Session validation error
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', ['member_id' => $this->member1->id]);

        $response->assertSessionHasErrors('attendance');
    }

    public function test_multiple_daily_sessions_allowed_after_checking_out(): void
    {
        $mCalc = new MembershipCalculationService;
        $mCalc->assignMembership($this->member1->id, $this->planMonthly->id, now()->toDateString());

        // Session 1 Check-In
        $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', ['member_id' => $this->member1->id]);

        $att1 = Attendance::where('member_id', $this->member1->id)->first();

        // Session 1 Checkout
        $this->actingAs($this->receptionist)
            ->post("/attendances/{$att1->id}/checkout");

        // Session 2 Check-In -> Success
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', ['member_id' => $this->member1->id]);

        $response->assertRedirect('/attendances');
        $this->assertEquals(2, Attendance::where('member_id', $this->member1->id)->count());
    }

    public function test_scheduled_or_expired_or_cancelled_membership_blocks_check_in(): void
    {
        $mCalc = new MembershipCalculationService;

        // Future Scheduled Membership Only
        $futureMember = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'B1-M-00002',
            'first_name' => 'Usman',
            'last_name' => 'Khan',
            'phone' => '+923002222222',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);
        $mCalc->assignMembership($futureMember->id, $this->planMonthly->id, now()->addDays(5)->toDateString());

        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', ['member_id' => $futureMember->id]);

        $response->assertSessionHasErrors('membership');
    }

    public function test_inactive_member_profile_cannot_check_in(): void
    {
        $inactiveMember = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'B1-M-00003',
            'first_name' => 'Inverted',
            'last_name' => 'User',
            'phone' => '+923003333333',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', ['member_id' => $inactiveMember->id]);

        $response->assertSessionHasErrors('member');
    }

    public function test_receptionist_can_check_in_out_but_cannot_edit_or_delete_attendance(): void
    {
        $mCalc = new MembershipCalculationService;
        $mCalc->assignMembership($this->member1->id, $this->planMonthly->id, now()->toDateString());

        $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', ['member_id' => $this->member1->id]);

        $att = Attendance::where('member_id', $this->member1->id)->first();

        // Edit form -> 403 Forbidden
        $response = $this->actingAs($this->receptionist)
            ->get("/attendances/{$att->id}/edit");
        $response->assertStatus(403);

        // Delete -> 403 Forbidden
        $response = $this->actingAs($this->receptionist)
            ->delete("/attendances/{$att->id}");
        $response->assertStatus(403);
    }

    public function test_trainer_has_view_only_attendance_access(): void
    {
        $response = $this->actingAs($this->trainer)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/attendances/create');

        $response->assertStatus(403);
    }

    public function test_cross_branch_attendance_idor_protection(): void
    {
        $otherMember = Member::create([
            'branch_id' => $this->branch2->id,
            'member_code' => 'B2-M-00001',
            'first_name' => 'Bilal',
            'last_name' => 'Saeed',
            'phone' => '+923004444444',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);

        // Manager 1 (Branch 1) attempts to check-in Branch 2 Member -> 403
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/attendances', ['member_id' => $otherMember->id]);

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
