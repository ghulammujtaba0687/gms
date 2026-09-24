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
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('restrict');
            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->enum('duration_type', ['days', 'months', 'years'])->default('months');
            $table->unsignedInteger('duration_value');
            $table->decimal('signup_fee', 12, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Virtual Generated Columns for Scoped Active Unique Constraint (ANSI SQL compatible)
            $table->unsignedBigInteger('scope_branch_id')->virtualAs('COALESCE(branch_id, 0)');
            $table->string('active_code', 100)->virtualAs('CASE WHEN deleted_at IS NULL THEN code ELSE NULL END');

            $table->unique(['scope_branch_id', 'active_code'], 'unique_active_plan_code_per_scope');
            $table->index(['branch_id', 'is_active']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
