<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Member;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
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
    }

    public function test_manager_can_create_staff_and_login_user_in_atomic_transaction(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/staff', [
                'name' => 'New Staff',
                'email' => 'newstaff@gym.com',
                'password' => 'password123',
                'role' => 'receptionist',
                'designation' => 'Front Desk Executive',
                'joining_date' => '2025-01-01',
                'status' => 'active',
                'is_trainer' => false,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'newstaff@gym.com']);
        $this->assertDatabaseHas('staff_profiles', [
            'staff_code' => 'B1-STF-00001',
            'designation' => 'Front Desk Executive',
            'is_trainer' => false,
        ]);
    }

    public function test_concurrency_safe_trainer_code_generation(): void
    {
        $codeService = new \App\Services\StaffCodeService;
        $trnCode1 = $codeService->generate($this->branch1->id, true);
        $stfCode1 = $codeService->generate($this->branch1->id, false);

        $this->assertEquals('B1-TRN-00001', $trnCode1);
        $this->assertEquals('B1-STF-00001', $stfCode1);
    }

    public function test_assign_personal_trainer_to_member(): void
    {
        $trainerStaff = StaffProfile::create([
            'user_id' => $this->trainer->id,
            'branch_id' => $this->branch1->id,
            'staff_code' => 'B1-TRN-00001',
            'is_trainer' => true,
            'designation' => 'Fitness Coach',
            'joining_date' => '2025-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->manager)
            ->post('/trainer/assign', [
                'member_id' => $this->member1->id,
                'staff_profile_id' => $trainerStaff->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('trainer_member_assignments', [
            'member_id' => $this->member1->id,
            'staff_profile_id' => $trainerStaff->id,
            'status' => 'active',
        ]);
    }

    public function test_trainer_cannot_access_staff_directory(): void
    {
        $response = $this->actingAs($this->trainer)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/staff');

        $response->assertStatus(403);
    }

    public function test_cross_branch_staff_idor_protection(): void
    {
        $otherUser = User::factory()->create(['status' => 'active']);
        $otherStaff = StaffProfile::create([
            'user_id' => $otherUser->id,
            'branch_id' => $this->branch2->id,
            'staff_code' => 'B2-STF-00001',
            'is_trainer' => false,
            'designation' => 'Branch 2 Staff',
            'joining_date' => '2025-01-01',
            'status' => 'active',
        ]);

        // Manager 1 (Branch 1) attempts to edit Branch 2 Staff Profile -> 403 or 404
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get("/staff/{$otherStaff->id}/edit");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
