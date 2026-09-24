<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected GymProfile $gym;

    protected Branch $branch;

    protected User $owner;

    protected User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->gym = GymProfile::create(['name' => 'Gym', 'currency' => 'PKR', 'timezone' => 'Asia/Karachi']);
        $this->branch = Branch::create(['gym_profile_id' => $this->gym->id, 'code' => 'B1', 'name' => 'Branch 1']);

        $this->owner = User::factory()->create(['status' => 'active']);
        $this->owner->roles()->attach(Role::where('name', 'owner')->first());

        $this->receptionist = User::factory()->create(['status' => 'active']);
        $this->receptionist->roles()->attach(Role::where('name', 'receptionist')->first());
        $this->receptionist->branches()->attach($this->branch);
    }

    public function test_owner_can_create_and_edit_branches(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession(['active_branch_id' => null])
            ->get('/branches/create');

        $response->assertStatus(200);

        $response = $this->actingAs($this->owner)
            ->post('/branches', [
                'code' => 'NEW1',
                'name' => 'New Branch',
                'is_active' => true,
            ]);

        $response->assertRedirect('/branches');
        $this->assertDatabaseHas('branches', ['code' => 'NEW1']);
    }

    public function test_receptionist_cannot_create_branches(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get('/branches/create');

        $response->assertStatus(403);
    }
}
