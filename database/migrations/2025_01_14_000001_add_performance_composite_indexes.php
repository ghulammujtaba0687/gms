<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'idx_members_branch_status');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->index(['branch_id', 'status', 'end_date'], 'idx_memberships_branch_status_end');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_branch_status');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropIndex('idx_memberships_branch_status_end');
        });
    }
};
