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
        try {
            \Illuminate\Support\Facades\DB::table('users')
                ->join('branches_agents', 'users.id', '=', 'branches_agents.user_id')
                ->whereNull('users.branch_agent_id')
                ->update(['users.branch_agent_id' => \Illuminate\Support\Facades\DB::raw('branches_agents.id')]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to sync branch_agent_id in migration: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed as this is a one-time data sync
    }
};
