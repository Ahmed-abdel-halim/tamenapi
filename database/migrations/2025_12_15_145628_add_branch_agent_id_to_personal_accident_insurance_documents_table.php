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
        Schema::table('personal_accident_insurance_documents', function (Blueprint $table) {
            $table->foreignId('branch_agent_id')->nullable()->after('total')->constrained('branches_agents')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_accident_insurance_documents', function (Blueprint $table) {
            $table->dropForeign(['branch_agent_id']);
            $table->dropColumn('branch_agent_id');
        });
    }
};
