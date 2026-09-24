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
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('member_id')->constrained('members')->onDelete('restrict');
            $table->foreignId('membership_plan_id')->constrained('membership_plans')->onDelete('restrict');

            // Price/Name Historical Snapshots
            $table->string('plan_name_snapshot', 150);
            $table->string('plan_code_snapshot', 50);
            $table->decimal('plan_price_snapshot', 12, 2);
            $table->decimal('plan_signup_fee_snapshot', 12, 2)->default(0.00);

            // Dates & Lifecycle Status
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['scheduled', 'active', 'expired', 'cancelled'])->default('active');
            $table->text('notes')->nullable();

            // Cancellation Details
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 255)->nullable();

            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['member_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
