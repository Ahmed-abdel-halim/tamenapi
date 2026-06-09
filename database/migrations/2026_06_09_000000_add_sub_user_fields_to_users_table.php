<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_agent_id')->nullable()->after('is_admin');
            $table->json('lifo_permissions')->nullable()->after('lifo_office_id');
            $table->string('lifo_user_id')->nullable()->after('lifo_permissions');

            $table->foreign('branch_agent_id')->references('id')->on('branches_agents')->onDelete('set null');
        });

        // Sync existing branch agents' user_id to branch_agent_id on users table
        try {
            DB::table('users')
                ->join('branches_agents', 'users.id', '=', 'branches_agents.user_id')
                ->update(['users.branch_agent_id' => DB::raw('branches_agents.id')]);
        } catch (\Exception $e) {
            // Log if anything fails, but keep migration succeeding
            Log::error("Failed to sync initial branch_agent_id fields: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_agent_id']);
            $table->dropColumn(['branch_agent_id', 'lifo_permissions', 'lifo_user_id']);
        });
    }
};
