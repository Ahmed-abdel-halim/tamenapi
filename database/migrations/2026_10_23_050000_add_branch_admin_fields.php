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
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_branch_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_branch_admin')->default(false)->after('is_admin');
            });
        }

        if (Schema::hasTable('branches') && !Schema::hasColumn('branches', 'manager_user_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->unsignedBigInteger('manager_user_id')->nullable()->after('manager_phone');
                $table->foreign('manager_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'manager_user_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropForeign(['manager_user_id']);
                $table->dropColumn('manager_user_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_branch_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_branch_admin');
            });
        }
    }
};
