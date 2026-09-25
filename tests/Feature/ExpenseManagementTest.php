<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GymProfile;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected GymProfile $gym;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $owner;
    protected User $manager;
    protected User $receptionist;
    protected User $trainer;
    protected ExpenseCategory $globalCategory;
    protected ExpenseCategory $b1Category;

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

        $this->globalCategory = ExpenseCategory::whereNull('branch_id')->first();
        $this->b1Category = ExpenseCategory::create([
            'branch_id' => $this->branch1->id,
            'code' => 'B1-CUSTOM',
            'name' => 'Branch 1 Custom Category',
            'is_active' => true,
        ]);
    }

    public function test_idempotent_expense_category_seeder_prevents_duplicates(): void
    {
        $initialCount = ExpenseCategory::count();

        // Run seeder second time
        $this->seed(ExpenseCategorySeeder::class);

        $this->assertEquals($initialCount, ExpenseCategory::count());
    }

    public function test_manager_and_owner_can_record_and_auto_approve_expense(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/expenses', [
                'expense_category_id' => $this->globalCategory->id,
                'title' => 'Monthly Electricity Bill',
                'amount' => 15000.50,
                'expense_date' => '2025-01-15',
                'payment_method' => 'bank_transfer',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'title' => 'Monthly Electricity Bill',
            'amount' => 15000.50,
            'status' => 'approved',
            'expense_code' => 'B1-EXP-00001',
            'branch_id' => $this->branch1->id,
        ]);
    }

    public function test_receptionist_expense_creation_is_recorded_pending_approval(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/expenses', [
                'expense_category_id' => $this->globalCategory->id,
                'title' => 'Cleaning Supplies',
                'amount' => 2500.00,
                'expense_date' => '2025-01-15',
                'payment_method' => 'cash',
            ]);

        $response->assertRedirect();
        $expense = Expense::where('title', 'Cleaning Supplies')->first();

        $this->assertEquals('recorded', $expense->status);
        $this->assertNull($expense->approved_at);

        // Manager approves pending expense
        $approveResponse = $this->actingAs($this->manager)->post("/expenses/{$expense->id}/approve");
        $approveResponse->assertRedirect();

        $expense->refresh();
        $this->assertEquals('approved', $expense->status);
        $this->assertEquals($this->manager->id, $expense->approved_by);
    }

    public function test_negative_or_zero_expense_amount_rejected(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/expenses', [
                'expense_category_id' => $this->globalCategory->id,
                'title' => 'Invalid Expense',
                'amount' => 0.00,
                'expense_date' => '2025-01-15',
                'payment_method' => 'cash',
            ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_receipt_proof_attachment_validation_and_storage(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('bill.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/expenses', [
                'expense_category_id' => $this->globalCategory->id,
                'title' => 'Equipment Repair',
                'amount' => 8000.00,
                'expense_date' => '2025-01-15',
                'payment_method' => 'cash',
                'receipt' => $file,
            ]);

        $response->assertRedirect();
        $expense = Expense::where('title', 'Equipment Repair')->first();

        $this->assertNotNull($expense->receipt_path);
        Storage::disk('public')->assertExists($expense->receipt_path);
    }

    public function test_trainer_cannot_access_expenses(): void
    {
        $response = $this->actingAs($this->trainer)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/expenses');

        $response->assertStatus(403);
    }

    public function test_cross_branch_expense_idor_protection(): void
    {
        $otherExpense = Expense::create([
            'branch_id' => $this->branch2->id,
            'expense_category_id' => $this->globalCategory->id,
            'expense_code' => 'B2-EXP-00001',
            'title' => 'Branch 2 Maintenance',
            'amount' => 5000.00,
            'expense_date' => '2025-01-15',
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        // Manager 1 (Branch 1) attempts to view Branch 2 expense -> 403 or 404
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get("/expenses/{$otherExpense->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_dashboard_expense_totals_and_cashflow_calculation(): void
    {
        $today = now()->toDateString();

        // 1 Approved Expense Today
        Expense::create([
            'branch_id' => $this->branch1->id,
            'expense_category_id' => $this->globalCategory->id,
            'expense_code' => 'B1-EXP-00001',
            'title' => 'Internet Bill',
            'amount' => 3000.00,
            'expense_date' => $today,
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Today\'s Expenses');
        $response->assertSee('Net Monthly Cashflow');
    }
}
