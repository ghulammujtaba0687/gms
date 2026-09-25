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
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');

            $table->string('staff_code', 30)->unique(); // e.g. DHA-STF-00001 or DHA-TRN-00001
            $table->boolean('is_trainer')->default(false);
            $table->string('designation', 100);
            $table->string('cnic', 20)->nullable();
            $table->string('specialization', 150)->nullable();
            $table->decimal('monthly_salary', 12, 2)->default(0.00);
            $table->date('joining_date');
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'is_trainer', 'status']);
            $table->index('staff_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
