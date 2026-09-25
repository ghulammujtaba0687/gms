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
        Schema::create('membership_freezes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('member_id')->constrained('members')->onDelete('restrict');
            $table->foreignId('membership_id')->constrained('memberships')->onDelete('restrict');

            $table->date('freeze_start_date');
            $table->date('freeze_end_date');
            $table->unsignedInteger('frozen_days');
            $table->string('reason', 255);
            $table->enum('status', ['pending', 'approved', 'active', 'completed', 'cancelled'])->default('pending');

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['membership_id', 'status']);
            $table->index(['freeze_start_date', 'freeze_end_date']);
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_freezes');
    }
};
