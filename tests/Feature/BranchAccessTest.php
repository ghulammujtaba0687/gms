<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAccessTest extends TestCase
{
    use RefreshDatabase;

    protected GymProfile $gym;

    protected Branch $branch1;

    protected Branch $branch2;

    protected User $owner;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->gym = GymProfile::create([
            'name' => 'Test Gym',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
        ]);

        $this->branch1 = Branch::create([
            'gym_profile_id' => $this->gym->id,
            'code' => 'B1',
            'name' => 'Branch One',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'gym_profile_id' => $this->gym->id,
            'code' => 'B2',
            'name' => 'Branch Two',
            'is_active' => true,
        ]);

        // Create Owner
        $this->owner = User::factory()->create(['status' => 'active']);
        $this->owner->roles()->attach(Role::where('name', 'owner')->first());

        // Create Manager assigned ONLY to Branch 1
        $this->manager = User::factory()->create(['status' => 'active']);
        $this->manager->roles()->attach(Role::where('name', 'manager')->first());
        $this->manager->branches()->attach($this->branch1);
    }

    public function test_owner_has_all_branches_access(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession(['active_branch_id' => null])
            ->get('/branches');

        $response->assertStatus(200);
        $response->assertSee('Branch One');
        $response->assertSee('Branch Two');
    }

    public function test_owner_can_switch_to_individual_branch(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/branch/switch', ['branch_id' => $this->branch1->id]);

        $response->assertRedirect();
        $this->assertEquals($this->branch1->id, session('active_branch_id'));
    }

    public function test_manager_can_access_assigned_branch(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_manager_denied_access_to_unassigned_branch_context(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch2->id])
            ->get('/dashboard');

        $response->assertStatus(403);
    }

    public function test_manager_cannot_switch_to_unassigned_branch(): void
    {
        $response = $this->actingAs($this->manager)
            ->post('/branch/switch', ['branch_id' => $this->branch2->id]);

        $response->assertStatus(403);
    }

    public function test_manager_cannot_switch_to_all_branches_mode(): void
    {
        $response = $this->actingAs($this->manager)
            ->post('/branch/switch', ['branch_id' => 'all']);

        $response->assertStatus(403);
    }
}
