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
        Schema::table('pos_machines', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_machines', 'branch_agent_id')) {
                $table->foreignId('branch_agent_id')->nullable()->after('is_active')
                      ->constrained('branches_agents')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_machines', function (Blueprint $table) {
            if (Schema::hasColumn('pos_machines', 'branch_agent_id')) {
                $table->dropForeign(['branch_agent_id']);
                $table->dropColumn('branch_agent_id');
            }
        });
    }
};
