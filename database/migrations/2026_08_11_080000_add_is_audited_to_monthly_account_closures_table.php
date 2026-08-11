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
        if (Schema::hasTable('monthly_account_closures')) {
            if (!Schema::hasColumn('monthly_account_closures', 'is_audited')) {
                Schema::table('monthly_account_closures', function (Blueprint $table) {
                    $table->boolean('is_audited')->default(false)->after('notes');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('monthly_account_closures')) {
            if (Schema::hasColumn('monthly_account_closures', 'is_audited')) {
                Schema::table('monthly_account_closures', function (Blueprint $table) {
                    $table->dropColumn('is_audited');
                });
            }
        }
    }
};
