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
            $table->text('contract_conditions')->nullable()->after('contract_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches_agents', function (Blueprint $table) {
            $table->dropColumn('contract_conditions');
        });
    }
};
