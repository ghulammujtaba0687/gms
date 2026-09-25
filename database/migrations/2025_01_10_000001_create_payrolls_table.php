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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->onDelete('restrict');
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();

            $table->string('payroll_code', 50)->unique(); // e.g. DHA-PRL-202501-00001
            $table->unsignedTinyInteger('payroll_month'); // 1 to 12
            $table->unsignedSmallInteger('payroll_year'); // e.g. 2025
            $table->string('salary_month_year', 10); // Format YYYY-MM

            $table->decimal('base_salary_snapshot', 12, 2);
            $table->decimal('bonus_amount', 12, 2)->default(0.00);
            $table->decimal('deduction_amount', 12, 2)->default(0.00);
            $table->decimal('net_salary', 12, 2);

            $table->string('payment_method', 50)->default('cash'); // cash, bank_transfer, easypaisa, jazzcash, other
            $table->date('payment_date');
            $table->string('reference_number', 100)->nullable();
            $table->enum('status', ['draft', 'processed', 'paid', 'cancelled'])->default('paid');
            $table->text('notes')->nullable();

            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // DB Constraint: Unique staff payroll per month/year
            $table->unique(['staff_profile_id', 'payroll_year', 'payroll_month'], 'unique_staff_monthly_payroll');
            $table->index(['branch_id', 'salary_month_year']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
