<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\MembershipPlan;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipPlanTest extends TestCase
{
    use RefreshDatabase;

    protected GymProfile $gym;

    protected Branch $branch1;

    protected Branch $branch2;

    protected User $owner;

    protected User $manager;

    protected User $receptionist;

    protected User $trainer;

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
    }

    public function test_owner_can_create_global_and_branch_specific_plans(): void
    {
        // Global Plan
        $response = $this->actingAs($this->owner)
            ->post('/membership-plans', [
                'scope' => 'global',
                'code' => 'GLOBAL-01',
                'name' => 'Global VIP Plan',
                'price' => 10000.00,
                'duration_type' => 'months',
                'duration_value' => 1,
                'is_active' => true,
            ]);

        $response->assertRedirect('/membership-plans');
        $this->assertDatabaseHas('membership_plans', [
            'code' => 'GLOBAL-01',
            'branch_id' => null,
        ]);

        // Branch Plan
        $response = $this->actingAs($this->owner)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/membership-plans', [
                'scope' => 'branch',
                'code' => 'B1-REG-01',
                'name' => 'Branch 1 Plan',
                'price' => 5000.00,
                'duration_type' => 'months',
                'duration_value' => 1,
                'is_active' => true,
            ]);

        $response->assertRedirect('/membership-plans');
        $this->assertDatabaseHas('membership_plans', [
            'code' => 'B1-REG-01',
            'branch_id' => $this->branch1->id,
        ]);
    }

    public function test_manager_can_create_assigned_branch_plan_but_cannot_modify_global_plan(): void
    {
        // Manager creates branch plan
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/membership-plans', [
                'scope' => 'branch',
                'code' => 'MGR-B1-01',
                'name' => 'Manager Branch Plan',
                'price' => 4500.00,
                'duration_type' => 'months',
                'duration_value' => 1,
                'is_active' => true,
            ]);

        $response->assertRedirect('/membership-plans');
        $this->assertDatabaseHas('membership_plans', ['code' => 'MGR-B1-01', 'branch_id' => $this->branch1->id]);

        // Global Plan
        $globalPlan = MembershipPlan::create([
            'branch_id' => null,
            'code' => 'GLOBAL-SPECIAL',
            'name' => 'Global Special',
            'price' => 12000.00,
            'duration_type' => 'years',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        // Manager tries to edit Global Plan -> 403 Forbidden
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get("/membership-plans/{$globalPlan->id}/edit");

        $response->assertStatus(403);
    }

    public function test_cross_branch_plan_isolation_prevents_manager_from_modifying_other_branch_plan(): void
    {
        $b2Plan = MembershipPlan::create([
            'branch_id' => $this->branch2->id,
            'code' => 'B2-PLAN-01',
            'name' => 'Branch 2 Plan',
            'price' => 6000.00,
            'duration_type' => 'months',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        // Manager 1 (Branch 1) tries to edit Branch 2 Plan - blocked by Scope or Policy
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get("/membership-plans/{$b2Plan->id}/edit");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_receptionist_and_trainer_have_view_only_access(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/membership-plans');
        $response->assertStatus(200);

        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/membership-plans/create');
        $response->assertStatus(403);

        $response = $this->actingAs($this->trainer)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/membership-plans/create');
        $response->assertStatus(403);
    }

    public function test_plan_code_immutability_and_validation(): void
    {
        $plan = MembershipPlan::create([
            'branch_id' => $this->branch1->id,
            'code' => 'FIXED-CODE-01',
            'name' => 'Original Name',
            'price' => 5000.00,
            'duration_type' => 'months',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->put("/membership-plans/{$plan->id}", [
                'name' => 'Updated Name',
                'price' => 5500.00,
                'duration_type' => 'months',
                'duration_value' => 1,
                'is_active' => true,
            ]);

        $response->assertRedirect('/membership-plans');
        $plan->refresh();
        $this->assertEquals('FIXED-CODE-01', $plan->code);
        $this->assertEquals('Updated Name', $plan->name);
    }

    public function test_invalid_price_and_duration_validation(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/membership-plans', [
                'scope' => 'global',
                'code' => 'INVALID-01',
                'name' => 'Invalid Plan',
                'price' => -50.00,
                'duration_type' => 'invalid_type',
                'duration_value' => 0,
            ]);

        $response->assertSessionHasErrors(['price', 'duration_type', 'duration_value']);
    }

    public function test_same_scope_duplicate_code_rejected_and_different_branch_same_code_allowed(): void
    {
        MembershipPlan::create([
            'branch_id' => $this->branch1->id,
            'code' => 'STD-01',
            'name' => 'Standard Plan',
            'price' => 3000.00,
            'duration_type' => 'months',
            'duration_value' => 1,
        ]);

        // Same branch duplicate code -> Rejected
        $response = $this->actingAs($this->owner)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/membership-plans', [
                'scope' => 'branch',
                'code' => 'STD-01',
                'name' => 'Duplicate Plan',
                'price' => 3000.00,
                'duration_type' => 'months',
                'duration_value' => 1,
            ]);

        $response->assertSessionHasErrors('code');

        // Different branch same code -> Allowed
        $response = $this->actingAs($this->owner)
            ->withSession(['active_branch_id' => $this->branch2->id])
            ->post('/membership-plans', [
                'scope' => 'branch',
                'code' => 'STD-01',
                'name' => 'Branch 2 Standard Plan',
                'price' => 3000.00,
                'duration_type' => 'months',
                'duration_value' => 1,
            ]);

        $response->assertRedirect('/membership-plans');
        $this->assertDatabaseHas('membership_plans', ['code' => 'STD-01', 'branch_id' => $this->branch2->id]);
    }

    public function test_soft_delete_and_restore_conflict_handling(): void
    {
        $plan1 = MembershipPlan::create([
            'branch_id' => $this->branch1->id,
            'code' => 'RESTORE-01',
            'name' => 'Plan 1',
            'price' => 2000.00,
            'duration_type' => 'months',
            'duration_value' => 1,
        ]);

        // Soft Delete Plan 1
        $response = $this->actingAs($this->owner)
            ->delete("/membership-plans/{$plan1->id}");
        $this->assertSoftDeleted('membership_plans', ['id' => $plan1->id]);

        // Create new active plan with SAME code in same branch (Allowed because previous is deleted)
        $plan2 = MembershipPlan::create([
            'branch_id' => $this->branch1->id,
            'code' => 'RESTORE-01',
            'name' => 'Plan 2 Active',
            'price' => 2500.00,
            'duration_type' => 'months',
            'duration_value' => 1,
        ]);

        // Try to Restore Plan 1 -> Rejected due to active code conflict
        $response = $this->actingAs($this->owner)
            ->post("/membership-plans/{$plan1->id}/restore");

        $response->assertSessionHas('error');
        $this->assertSoftDeleted('membership_plans', ['id' => $plan1->id]);

        // Soft Delete Plan 2
        $plan2->delete();

        // Now Restore Plan 1 -> Allowed
        $response = $this->actingAs($this->owner)
            ->post("/membership-plans/{$plan1->id}/restore");

        $response->assertRedirect('/membership-plans');
        $this->assertNotSoftDeleted('membership_plans', ['id' => $plan1->id]);
    }
}
