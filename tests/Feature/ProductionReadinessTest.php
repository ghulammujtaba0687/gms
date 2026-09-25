<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $managerBranch1;

    protected User $managerBranch2;

    protected Branch $branch1;

    protected Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['display_name' => 'Gym Owner']);
        $managerRole = Role::firstOrCreate(['name' => 'manager'], ['display_name' => 'Manager']);

        $this->branch1 = Branch::create(['name' => 'DHA Branch', 'code' => 'DHA', 'is_active' => true]);
        $this->branch2 = Branch::create(['name' => 'Gulberg Branch', 'code' => 'GLB', 'is_active' => true]);

        $this->owner = User::factory()->create(['status' => 'active']);
        $this->owner->roles()->attach($ownerRole->id);

        $this->managerBranch1 = User::factory()->create(['status' => 'active']);
        $this->managerBranch1->roles()->attach($managerRole->id);
        $this->managerBranch1->branches()->attach($this->branch1->id);

        $this->managerBranch2 = User::factory()->create(['status' => 'active']);
        $this->managerBranch2->roles()->attach($managerRole->id);
        $this->managerBranch2->branches()->attach($this->branch2->id);
    }

    public function test_health_check_endpoint_returns_healthy(): void
    {
        $response = $this->get(route('health'));

        $response->assertOk();
        $response->assertJson([
            'status' => 'healthy',
            'checks' => [
                'database' => 'ok',
                'storage' => 'writable',
            ],
        ]);
    }

    public function test_backup_command_executes_successfully(): void
    {
        $exitCode = Artisan::call('gms:backup');

        $this->assertEquals(0, $exitCode);
    }

    public function test_secure_file_streaming_allows_authorized_branch_access(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('proof.pdf', 100);
        $path = $file->storeAs('payments/proofs/1', 'test_proof.pdf', 'public');

        $member = Member::create([
            'branch_id' => $this->branch1->id,
            'member_code' => 'DHA-M-00001',
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'cnic' => '35202-1234567-1',
            'phone' => '03001234567',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $payment = Payment::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $member->id,
            'payment_code' => 'DHA-PAY-00001',
            'amount_due' => 5000,
            'amount_paid' => 5000,
            'discount_amount' => 0,
            'remaining_balance' => 0,
            'payment_method' => 'bank_transfer',
            'proof_path' => $path,
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
            'recorded_by' => $this->managerBranch1->id,
        ]);

        $response = $this->actingAs($this->managerBranch1)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->get(route('files.payments.proof', $payment->id));

        $response->assertOk();
    }

    public function test_secure_file_streaming_blocks_cross_branch_idor_attempt(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('receipt.pdf', 100);
        $path = $file->storeAs('expenses/receipts/1', 'receipt.pdf', 'public');

        $cat = ExpenseCategory::create(['name' => 'Rent', 'is_active' => true]);

        $expense = Expense::create([
            'branch_id' => $this->branch1->id,
            'expense_category_id' => $cat->id,
            'expense_code' => 'DHA-EXP-00001',
            'title' => 'DHA Gym Rent',
            'amount' => 50000,
            'payment_method' => 'bank_transfer',
            'receipt_path' => $path,
            'expense_date' => now()->toDateString(),
            'status' => 'approved',
            'recorded_by' => $this->managerBranch1->id,
        ]);

        // Manager Branch 2 attempts to view Branch 1 expense receipt
        $response = $this->actingAs($this->managerBranch2)
            ->withSession(['active_branch_id' => $this->branch2->id])
            ->get(route('files.expenses.receipt', $expense->id));

        $response->assertStatus(403);
    }
}
