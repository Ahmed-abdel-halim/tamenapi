<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $cancelledAgentIds = DB::table('agency_cancellations')
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('branch_agent_id')
            ->toArray();

        if (!empty($cancelledAgentIds)) {
            DB::table('branches_agents')
                ->whereIn('id', $cancelledAgentIds)
                ->update(['status' => 'غير نشط']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};