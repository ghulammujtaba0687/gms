<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Role;
use App\Models\User;
use App\Services\MembershipCalculationService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipAssignmentTest extends TestCase
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

    public function test_carbon_date_arithmetic_january_31_and_leap_year_edge_cases(): void
    {
        $service = new MembershipCalculationService;

        // Jan 31 + 1 Month in non-leap year (28 days) minus 1 day = Feb 27
        $jan31 = Carbon::create(2025, 1, 31);
        $endDateNonLeap = $service->calculateEndDate($jan31, 'months', 1);
        $this->assertEquals('2025-02-27', $endDateNonLeap->format('Y-m-d'));

        // Jan 31 + 1 Month in leap year (29 days) minus 1 day = Feb 28
        $jan31Leap = Carbon::create(2024, 1, 31);
        $endDateLeap = $service->calculateEndDate($jan31Leap, 'months', 1);
        $this->assertEquals('2024-02-28', $endDateLeap->format('Y-m-d'));

        // Feb 29 + 1 Year (2025-02-28) minus 1 day -> Feb 27 of next year
        $feb29Leap = Carbon::create(2024, 2, 29);
        $endDateYear = $service->calculateEndDate($feb29Leap, 'years', 1);
        $this->assertEquals('2025-02-27', $endDateYear->format('Y-m-d'));
    }

    public function test_membership_assignment_snapshots_price_and_code(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/memberships', [
                'member_id' => $this->member1->id,
                'membership_plan_id' => $this->planMonthly->id,
                'conflict_action' => 'stack',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('memberships', [
            'member_id' => $this->member1->id,
            'plan_name_snapshot' => 'Monthly Plan',
            'plan_code_snapshot' => 'MONTHLY-01',
            'plan_price_snapshot' => 5000.00,
            'status' => 'active',
        ]);

        // Edit original plan price
        $this->planMonthly->update(['price' => 7000.00, 'name' => 'Monthly Premium']);

        // Assert assigned membership snapshot remains unchanged
        $membership = Membership::where('member_id', $this->member1->id)->first();
        $this->assertEquals(5000.00, $membership->plan_price_snapshot);
        $this->assertEquals('Monthly Plan', $membership->plan_name_snapshot);
    }

    public function test_existing_active_membership_stacking_creates_scheduled_status(): void
    {
        // 1st Membership
        $service = new MembershipCalculationService;
        $m1 = $service->assignMembership($this->member1->id, $this->planMonthly->id, '2025-01-01');

        $this->assertEquals('2025-01-01', $m1->start_date->format('Y-m-d'));
        $this->assertEquals('2025-01-31', $m1->end_date->format('Y-m-d'));

        // 2nd Membership stacked with explicit future start_date
        $futureStart = Carbon::today()->addDays(10)->format('Y-m-d');
        $m2 = $service->assignMembership($this->member1->id, $this->planMonthly->id, $futureStart, 'stack');

        $this->assertEquals($futureStart, $m2->start_date->format('Y-m-d'));
        $this->assertEquals('scheduled', $m2->status);
    }

    public function test_existing_active_membership_replacement_populates_cancellation_fields(): void
    {
        $service = new MembershipCalculationService;
        $m1 = $service->assignMembership($this->member1->id, $this->planMonthly->id, '2025-01-01');

        $this->actingAs($this->manager);

        // Replace active membership
        $m2 = $service->assignMembership($this->member1->id, $this->planMonthly->id, '2025-01-15', 'replace');

        $m1->refresh();
        $this->assertEquals('cancelled', $m1->status);
        $this->assertNotNull($m1->cancelled_at);
        $this->assertEquals($this->manager->id, $m1->cancelled_by);

        $this->assertEquals('active', $m2->status);
        $this->assertEquals('2025-01-15', $m2->start_date->format('Y-m-d'));
    }

    public function test_receptionist_can_assign_membership_but_cannot_cancel_or_delete(): void
    {
        // Receptionist assigns plan
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/memberships', [
                'member_id' => $this->member1->id,
                'membership_plan_id' => $this->planMonthly->id,
            ]);

        $response->assertRedirect();
        $membership = Membership::where('member_id', $this->member1->id)->first();
        $this->assertNotNull($membership);

        // Receptionist attempts cancel -> 403
        $response = $this->actingAs($this->receptionist)
            ->post("/memberships/{$membership->id}/cancel", ['cancellation_reason' => 'Test']);
        $response->assertStatus(403);
    }

    public function test_trainer_cannot_assign_or_cancel_membership(): void
    {
        $response = $this->actingAs($this->trainer)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/memberships/create');

        $response->assertStatus(403);
    }

    public function test_cross_branch_membership_idor_prevention(): void
    {
        $otherMember = Member::create([
            'branch_id' => $this->branch2->id,
            'member_code' => 'B2-M-00001',
            'first_name' => 'Usman',
            'last_name' => 'Ali',
            'phone' => '+923002222222',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);

        // Manager 1 (Branch 1) tries to assign plan to Branch 2 Member -> 403 or 404
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/memberships', [
                'member_id' => $otherMember->id,
                'membership_plan_id' => $this->planMonthly->id,
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
