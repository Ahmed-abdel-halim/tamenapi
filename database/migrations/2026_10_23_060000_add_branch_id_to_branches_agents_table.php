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
        Schema::table('branches_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('branches_agents', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('type')->constrained('branches')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches_agents', function (Blueprint $table) {
            if (Schema::hasColumn('branches_agents', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};