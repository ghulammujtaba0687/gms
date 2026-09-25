<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsAndAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $manager;

    protected User $receptionist;

    protected Branch $branch1;

    protected Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['display_name' => 'Gym Owner']);
        $managerRole = Role::firstOrCreate(['name' => 'manager'], ['display_name' => 'Manager']);
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist'], ['display_name' => 'Receptionist']);

        $this->branch1 = Branch::create(['name' => 'DHA Branch', 'code' => 'DHA', 'is_active' => true]);
        $this->branch2 = Branch::create(['name' => 'Gulberg Branch', 'code' => 'GLB', 'is_active' => true]);

        $this->owner = User::factory()->create(['status' => 'active']);
        $this->owner->roles()->attach($ownerRole->id);

        $this->manager = User::factory()->create(['status' => 'active']);
        $this->manager->roles()->attach($managerRole->id);
        $this->manager->branches()->attach($this->branch1->id);

        $this->receptionist = User::factory()->create(['status' => 'active']);
        $this->receptionist->roles()->attach($receptionistRole->id);
        $this->receptionist->branches()->attach($this->branch1->id);
    }

    public function test_setting_service_cascades_branch_overrides_to_global_defaults(): void
    {
        SettingService::set('gym_name', 'Global Titan Gym', null, 'general');
        SettingService::set('gym_name', 'DHA Titan Elite', $this->branch1->id, 'general');

        $this->assertEquals('DHA Titan Elite', SettingService::get('gym_name', null, $this->branch1->id));
        $this->assertEquals('Global Titan Gym', SettingService::get('gym_name', null, $this->branch2->id));
    }

    public function test_owner_can_update_settings_and_branding_with_logo_upload(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->owner)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->put(route('settings.update'), [
                'gym_name' => 'Updated Gym Name',
                'currency_symbol' => 'PKR',
                'receipt_footer_terms' => 'Thank you for training with us!',
                'freeze_max_days' => 15,
                'logo' => UploadedFile::fake()->image('logo.png'),
            ]);

        $response->assertRedirect();
        $this->assertEquals('Updated Gym Name', SettingService::get('gym_name', null, $this->branch1->id));
        $this->assertEquals(15, SettingService::get('freeze_max_days', 30, $this->branch1->id));
    }

    public function test_receptionist_cannot_access_settings_portal(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get(route('settings.index'));

        $response->assertStatus(403);
    }

    public function test_audit_logs_portal_renders_with_search_filters(): void
    {
        ActivityLog::create([
            'branch_id' => $this->branch1->id,
            'user_id' => $this->owner->id,
            'module' => 'Settings',
            'action' => 'Update Branding',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->owner)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get(route('audit-logs.index', ['module' => 'Settings']));

        $response->assertOk();
        $response->assertSee('Settings');
    }
}
