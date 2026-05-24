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
        // 1. Drop branch_agent_id from pos_machines
        Schema::table('pos_machines', function (Blueprint $table) {
            if (Schema::hasColumn('pos_machines', 'branch_agent_id')) {
                $table->dropForeign(['branch_agent_id']);
                $table->dropColumn('branch_agent_id');
            }
        });

        // 2. Create the pivot table agent_pos_machine
        Schema::create('agent_pos_machine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_machine_id')->constrained('pos_machines')->onDelete('cascade');
            $table->foreignId('branch_agent_id')->constrained('branches_agents')->onDelete('cascade');
            $table->timestamps();

            // Composite unique constraint
            $table->unique(['pos_machine_id', 'branch_agent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop pivot table
        Schema::dropIfExists('agent_pos_machine');

        // 2. Re-add branch_agent_id to pos_machines
        Schema::table('pos_machines', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_machines', 'branch_agent_id')) {
                $table->foreignId('branch_agent_id')->nullable()->after('is_active')
                      ->constrained('branches_agents')->onDelete('set null');
            }
        });
    }
};
