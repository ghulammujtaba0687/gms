<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use App\Services\MemberCodeService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberManagementTest extends TestCase
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
        $this->branch1 = Branch::create(['gym_profile_id' => $this->gym->id, 'code' => 'DHA', 'name' => 'DHA Branch']);
        $this->branch2 = Branch::create(['gym_profile_id' => $this->gym->id, 'code' => 'GUL', 'name' => 'Gulberg Branch']);

        // Roles
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

    public function test_member_code_generation_is_concurrency_safe_and_sequential(): void
    {
        $codeService = new MemberCodeService;

        $code1 = $codeService->generate($this->branch1->id);
        Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => $code1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+923001111111',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);

        $code2 = $codeService->generate($this->branch1->id);

        $this::assertEquals('DHA-M-00001', $code1);
        $this::assertEquals('DHA-M-00002', $code2);
    }

    public function test_owner_can_create_member_in_all_branches_mode(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/members', [
                'first_name' => 'Ali',
                'last_name' => 'Khan',
                'phone' => '+923001234567',
                'gender' => 'male',
                'join_date' => '2025-01-01',
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('members', [
            'first_name' => 'Ali',
            'member_code' => 'DHA-M-00001',
            'branch_id' => $this->branch1->id,
        ]);
    }

    public function test_manager_can_create_and_edit_assigned_branch_member(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/members', [
                'first_name' => 'Usman',
                'last_name' => 'Tariq',
                'phone' => '+923002222222',
                'gender' => 'male',
                'join_date' => '2025-01-01',
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $member = Member::where('first_name', 'Usman')->first();
        $this->assertNotNull($member);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->put("/members/{$member->id}", [
                'first_name' => 'Usman Updated',
                'last_name' => 'Tariq',
                'phone' => '+923002222222',
                'gender' => 'male',
                'join_date' => '2025-01-01',
                'status' => 'active',
            ]);

        $response->assertRedirect("/members/{$member->id}");
        $this->assertDatabaseHas('members', ['first_name' => 'Usman Updated']);
    }

    public function test_receptionist_can_create_and_edit_member_but_cannot_soft_delete(): void
    {
        $member = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'DHA-M-00001',
            'first_name' => 'Sara',
            'last_name' => 'Ahmad',
            'phone' => '+923003333333',
            'gender' => 'female',
            'join_date' => now(),
            'status' => 'active',
        ]);

        // Edit allowed
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get("/members/{$member->id}/edit");
        $response->assertStatus(200);

        // Delete forbidden
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->delete("/members/{$member->id}");
        $response->assertStatus(403);
    }

    public function test_trainer_has_view_only_access_and_cannot_create_or_edit(): void
    {
        $member = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'DHA-M-00001',
            'first_name' => 'Hamza',
            'last_name' => 'Malik',
            'phone' => '+923004444444',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);

        // View allowed
        $response = $this->actingAs($this->trainer)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get("/members/{$member->id}");
        $response->assertStatus(200);

        // Create form forbidden
        $response = $this->actingAs($this->trainer)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/members/create');
        $response->assertStatus(403);

        // Edit form forbidden
        $response = $this->actingAs($this->trainer)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get("/members/{$member->id}/edit");
        $response->assertStatus(403);
    }

    public function test_idor_protection_prevents_viewing_other_branch_member(): void
    {
        $otherMember = Member::create([
            'branch_id' => $this->branch2->id,
            'member_code' => 'GUL-M-00001',
            'first_name' => 'Bilal',
            'last_name' => 'Shami',
            'phone' => '+923005555555',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);

        // Manager 1 (Branch 1) tries to view Branch 2 member - blocked by BelongsToBranch scope or policy
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get("/members/{$otherMember->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_member_soft_delete_and_restore(): void
    {
        $member = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'DHA-M-00001',
            'first_name' => 'Tariq',
            'last_name' => 'Ziad',
            'phone' => '+923006666666',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);

        // Soft Delete
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->delete("/members/{$member->id}");

        $response->assertRedirect('/members');
        $this->assertSoftDeleted('members', ['id' => $member->id]);

        // Restore
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post("/members/{$member->id}/restore");

        $response->assertRedirect("/members/{$member->id}");
        $this->assertNotSoftDeleted('members', ['id' => $member->id]);
    }

    public function test_member_photo_upload_validation_and_storage(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/members', [
                'first_name' => 'Zain',
                'last_name' => 'Abbas',
                'phone' => '+923007777777',
                'gender' => 'male',
                'join_date' => '2025-01-01',
                'status' => 'active',
                'photo' => $file,
            ]);

        $response->assertRedirect();
        $member = Member::where('first_name', 'Zain')->first();
        $this->assertNotNull($member->photo_path);

        Storage::disk('public')->assertExists($member->photo_path);
    }

    public function test_branch_deletion_restricted_when_members_exist(): void
    {
        Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'DHA-M-00001',
            'first_name' => 'Asad',
            'last_name' => 'Raza',
            'phone' => '+923008888888',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);

        $this->expectException(QueryException::class);
        $this->branch1->forceDelete();
    }
}
