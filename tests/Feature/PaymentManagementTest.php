<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
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

    public function test_receptionist_can_record_full_payment_and_generate_receipt(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/payments', [
                'member_id' => $this->member1->id,
                'amount_due' => 5000.00,
                'amount_paid' => 5000.00,
                'discount_amount' => 0.00,
                'payment_method' => 'cash',
                'payment_date' => '2025-01-10',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'member_id' => $this->member1->id,
            'amount_due' => 5000.00,
            'amount_paid' => 5000.00,
            'remaining_balance' => 0.00,
            'status' => 'paid',
            'payment_code' => 'B1-PAY-00001',
        ]);

        $payment = Payment::where('member_id', $this->member1->id)->first();

        // Printable Receipt Check
        $receiptResponse = $this->actingAs($this->receptionist)->get("/payments/{$payment->id}/receipt");
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('B1-PAY-00001');
    }

    public function test_partial_payment_calculates_remaining_dues_and_discount(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/payments', [
                'member_id' => $this->member1->id,
                'amount_due' => 10000.00,
                'discount_amount' => 1000.00, // Net due = 9000
                'amount_paid' => 4000.00, // Remaining balance = 5000
                'discount_reason' => 'New Year Promo',
                'payment_method' => 'cash',
                'payment_date' => '2025-01-10',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'member_id' => $this->member1->id,
            'amount_due' => 10000.00,
            'discount_amount' => 1000.00,
            'amount_paid' => 4000.00,
            'remaining_balance' => 5000.00,
            'status' => 'partial',
        ]);

        $this->assertEquals(5000.00, $this->member1->fresh()->total_outstanding_dues);
    }

    public function test_online_transfer_proof_upload_and_manager_verification(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('transfer.png', 400, 400);

        $response = $this->actingAs($this->receptionist)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/payments', [
                'member_id' => $this->member1->id,
                'amount_due' => 5000.00,
                'amount_paid' => 5000.00,
                'payment_method' => 'easypaisa',
                'reference_number' => 'TRX-12345',
                'payment_date' => '2025-01-10',
                'proof' => $file,
            ]);

        $response->assertRedirect();
        $payment = Payment::where('member_id', $this->member1->id)->first();

        $this->assertEquals('pending_verification', $payment->status);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('public')->assertExists($payment->proof_path);

        // Manager Verifies Proof
        $verifyResponse = $this->actingAs($this->manager)->post("/payments/{$payment->id}/verify");
        $verifyResponse->assertRedirect();

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals($this->manager->id, $payment->verified_by);
    }

    public function test_manager_can_issue_refund_and_receptionist_blocked(): void
    {
        $payment = Payment::create([
            'branch_id' => $this->branch1->id,
            'member_id' => $this->member1->id,
            'payment_code' => 'B1-PAY-00001',
            'amount_due' => 5000.00,
            'amount_paid' => 5000.00,
            'remaining_balance' => 0.00,
            'payment_method' => 'cash',
            'payment_date' => '2025-01-10',
            'status' => 'paid',
        ]);

        // Receptionist attempts refund -> 403 Forbidden
        $response = $this->actingAs($this->receptionist)
            ->post("/payments/{$payment->id}/refund", [
                'refund_amount' => 5000.00,
                'refund_reason' => 'Cancelled membership',
                'refund_method' => 'cash',
            ]);
        $response->assertStatus(403);

        // Manager processes refund -> Success
        $response = $this->actingAs($this->manager)
            ->post("/payments/{$payment->id}/refund", [
                'refund_amount' => 5000.00,
                'refund_reason' => 'Cancelled membership',
                'refund_method' => 'cash',
            ]);

        $response->assertRedirect();
        $payment->refresh();
        $this->assertEquals('refunded', $payment->status);
        $this->assertDatabaseHas('refunds', [
            'payment_id' => $payment->id,
            'refund_amount' => 5000.00,
            'refund_code' => 'B1-RFD-00001',
        ]);
    }

    public function test_cross_branch_payment_idor_prevention(): void
    {
        $otherMember = Member::create([
            'branch_id' => $this->branch2->id,
            'member_code' => 'B2-M-00001',
            'first_name' => 'Usman',
            'last_name' => 'Ali',
            'phone' => '+923002222222',
            'gender' => 'male',
            'join_date' => now(),
            'status' => 'active',
        ]);

        // Manager 1 (Branch 1) attempts to record payment for Branch 2 member -> 403
        $response = $this->actingAs($this->manager)
            ->withSession(['active_branch_id' => $this->branch1->id])
            ->post('/payments', [
                'member_id' => $otherMember->id,
                'amount_due' => 3000.00,
                'amount_paid' => 3000.00,
                'payment_method' => 'cash',
                'payment_date' => '2025-01-10',
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
