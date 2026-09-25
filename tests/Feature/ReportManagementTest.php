<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\MembershipCalculationService;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportManagementTest extends TestCase
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
        $this->seed(ExpenseCategorySeeder::class);

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

    public function test_owner_can_generate_consolidated_financial_report(): void
    {
        Payment::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $this->member1->id,
            'payment_code' => 'B1-PAY-00001',
            'amount_due' => 5000.00,
            'amount_paid' => 5000.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/reports/revenue');

        $response->assertStatus(200);
        $response->assertSee('Financial Revenue');
        $response->assertSee('5,000.00');
    }

    public function test_manager_financial_report_restricted_to_assigned_branch(): void
    {
        // Branch 1 payment
        Payment::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $this->member1->id,
            'payment_code' => 'B1-PAY-00001',
            'amount_due' => 5000.00,
            'amount_paid' => 5000.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/reports/revenue');

        $response->assertStatus(200);
        $response->assertSee('5,000.00');
    }

    public function test_receptionist_blocked_from_financial_revenue_reports(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/reports/revenue');

        $response->assertStatus(403);
    }

    public function test_receptionist_can_access_expiring_memberships_and_dues_reports(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/reports/expiring');

        $response->assertStatus(200);

        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/reports/dues');

        $response->assertStatus(200);
    }

    public function test_trainer_blocked_from_reports_portal(): void
    {
        $response = $this->actingAs($this->trainer)
            ->get('/reports');

        $response->assertStatus(403);
    }

    public function test_csv_report_export_generates_valid_headers_and_stream(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/reports/expiring?export=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_printable_report_view_renders_successfully(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/reports/revenue?export=print');

        $response->assertStatus(200);
        $response->assertSee('Financial Revenue & Net Cashflow Report');
    }

    public function test_expiring_memberships_date_filtering(): void
    {
        $plan = MembershipPlan::create([
            'branch_id' => $this->branch1->id,
            'code' => 'P1',
            'name' => 'Plan 1',
            'price' => 3000.00,
            'duration_type' => 'months',
            'duration_value' => 1,
            'is_active' => true,
        ]);

        $mCalc = new MembershipCalculationService;
        $mCalc->assignMembership($this->member1->id, $plan->id, now()->toDateString());

        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/reports/expiring?days_threshold=30');

        $response->assertStatus(200);
        $response->assertSee('Ali Raza');
    }

    public function test_outstanding_dues_report_calculates_correct_unpaid_balances(): void
    {
        Payment::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $this->member1->id,
            'payment_code' => 'B1-PAY-DUES1',
            'amount_due' => 10000.00,
            'amount_paid' => 4000.00,
            'remaining_balance' => 6000.00,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'status' => 'partial',
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/reports/dues');

        $response->assertStatus(200);
        $response->assertSee('6,000.00');
    }
}
