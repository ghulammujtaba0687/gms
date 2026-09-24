<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('member_id')->constrained('members')->onDelete('restrict');
            $table->foreignId('membership_id')->nullable()->constrained('memberships')->onDelete('restrict');

            $table->string('payment_code', 30)->unique(); // e.g. DHA-PAY-00001
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->string('discount_reason', 255)->nullable();
            $table->decimal('remaining_balance', 12, 2)->default(0.00);

            $table->string('payment_method', 50)->default('cash'); // cash, bank_transfer, easypaisa, jazzcash, other
            $table->string('reference_number', 100)->nullable();
            $table->string('proof_path')->nullable(); // proof attachment for bank/easypaisa transfers
            $table->date('payment_date');

            $table->enum('status', ['paid', 'partial', 'pending_verification', 'voided', 'refunded'])->default('paid');
            $table->text('notes')->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['member_id', 'status']);
            $table->index(['branch_id', 'payment_date']);
            $table->index('payment_code');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('payment_id')->constrained('payments')->onDelete('restrict');
            $table->foreignId('member_id')->constrained('members')->onDelete('restrict');

            $table->string('refund_code', 30)->unique(); // e.g. DHA-RFD-00001
            $table->decimal('refund_amount', 12, 2);
            $table->string('refund_reason', 255);
            $table->string('refund_method', 50)->default('cash');
            $table->date('refund_date');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('payment_id');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
