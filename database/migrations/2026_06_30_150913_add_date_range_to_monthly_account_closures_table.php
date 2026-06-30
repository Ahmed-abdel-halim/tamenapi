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
        Schema::table('monthly_account_closures', function (Blueprint $table) {
            $table->integer('year')->nullable()->change();
            $table->integer('month')->nullable()->change();
            $table->date('from_date')->nullable()->after('month');
            $table->date('to_date')->nullable()->after('from_date');
            
            // Add unique index for custom date range closures
            $table->unique(['branch_agent_id', 'from_date', 'to_date'], 'unique_agent_range');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_account_closures', function (Blueprint $table) {
            $table->dropUnique('unique_agent_range');
            $table->dropColumn(['from_date', 'to_date']);
            $table->integer('year')->nullable(false)->change();
            $table->integer('month')->nullable(false)->change();
        });
    }
};
