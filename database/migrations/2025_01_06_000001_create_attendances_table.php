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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('member_id')->constrained('members')->onDelete('restrict');
            $table->foreignId('membership_id')->constrained('memberships')->onDelete('restrict');

            $table->dateTime('check_in_time');
            $table->dateTime('check_out_time')->nullable();
            $table->date('check_in_date');
            $table->enum('status', ['present', 'checked_out'])->default('present');
            $table->text('notes')->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for fast lookup without unique daily constraint
            $table->index(['member_id', 'status']);
            $table->index(['check_in_date', 'branch_id']);
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
