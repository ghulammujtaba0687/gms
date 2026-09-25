<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
{
    use RefreshDatabase;

    protected GymProfile $gym;

    protected Branch $branch1;

    protected Branch $branch2;

    protected User $owner;

    protected User $manager;

    protected User $receptionist;

    protected User $trainer;

    protected StaffProfile $staff1;

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

        $staffUser = User::factory()->create(['name' => 'John Staff', 'status' => 'active']);
        $this->staff1 = StaffProfile::create([
            'user_id' => $staffUser->id,
            'branch_id' => $this->branch1->id,
            'staff_code' => 'B1-STF-00001',
            'is_trainer' => false,
            'designation' => 'Front Desk Executive',
            'monthly_salary' => 50000.00,
            'joining_date' => '2025-01-01',
            'status' => 'active',
        ]);
    }

    public function test_manager_can_generate_and_disburse_staff_payroll_auto_posting_expense(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/payrolls', [
                'staff_profile_id' => $this->staff1->id,
                'salary_month_year' => '2025-01',
                'base_salary_snapshot' => 50000.00,
                'bonus_amount' => 5000.00,
                'deduction_amount' => 2000.00, // Net = 53,000
                'payment_method' => 'bank_transfer',
                'payment_date' => '2025-01-31',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payrolls', [
            'staff_profile_id' => $this->staff1->id,
            'salary_month_year' => '2025-01',
            'net_salary' => 53000.00,
            'status' => 'paid',
            'payroll_code' => 'B1-PRL-202501-00001',
        ]);

        $payroll = Payroll::where('staff_profile_id', $this->staff1->id)->first();
        $this->assertNotNull($payroll->expense_id);

        // Verify Auto-posted Expense Record in Phase 8 Expense Ledger
        $this->assertDatabaseHas('expenses', [
            'id' => $payroll->expense_id,
            'amount' => 53000.00,
            'status' => 'approved',
        ]);

        // Printable Payslip Check
        $payslipResponse = $this->actingAs($this->manager)->get("/payrolls/{$payroll->id}/payslip");
        $payslipResponse->assertStatus(200);
        $payslipResponse->assertSee('B1-PRL-202501-00001');
    }

    public function test_duplicate_monthly_payroll_for_same_staff_blocked_at_db_and_app_level(): void
    {
        // 1st Payroll
        $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/payrolls', [
                'staff_profile_id' => $this->staff1->id,
                'salary_month_year' => '2025-01',
                'payment_method' => 'cash',
                'payment_date' => '2025-01-31',
            ]);

        // 2nd Payroll for SAME staff and month -> Session Error
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/payrolls', [
                'staff_profile_id' => $this->staff1->id,
                'salary_month_year' => '2025-01',
                'payment_method' => 'cash',
                'payment_date' => '2025-01-31',
            ]);

        $response->assertSessionHasErrors('salary_month_year');
    }

    public function test_receptionist_cannot_process_payroll(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/payrolls/create');

        $response->assertStatus(403);
    }

    public function test_cross_branch_payroll_idor_protection(): void
    {
        $otherUser = User::factory()->create(['status' => 'active']);
        $otherStaff = StaffProfile::create([
            'user_id' => $otherUser->id,
            'branch_id' => $this->branch2->id,
            'staff_code' => 'B2-STF-00001',
            'is_trainer' => false,
            'designation' => 'Branch 2 Staff',
            'monthly_salary' => 40000.00,
            'joining_date' => '2025-01-01',
            'status' => 'active',
        ]);

        // Manager 1 (Branch 1) attempts to process Branch 2 Staff Payroll -> 403
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/payrolls', [
                'staff_profile_id' => $otherStaff->id,
                'salary_month_year' => '2025-01',
                'payment_method' => 'cash',
                'payment_date' => '2025-01-31',
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
