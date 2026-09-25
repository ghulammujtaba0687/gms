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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('expense_category_id')->constrained('expense_categories')->onDelete('restrict');

            $table->string('expense_code', 30)->unique(); // e.g. DHA-EXP-00001
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('payment_method', 50)->default('cash'); // cash, bank_transfer, easypaisa, jazzcash, other
            $table->string('vendor_name', 150)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('receipt_path')->nullable();

            $table->enum('status', ['recorded', 'approved', 'cancelled'])->default('recorded');
            $table->text('notes')->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'expense_date']);
            $table->index('expense_category_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
