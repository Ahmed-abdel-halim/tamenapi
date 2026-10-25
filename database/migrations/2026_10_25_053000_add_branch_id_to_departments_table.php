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
        if (Schema::hasTable('departments') && !Schema::hasColumn('departments', 'branch_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            });

            // Assign existing departments to main branch
            $mainBranchId = DB::table('branches')->where('is_main_branch', true)->value('id')
                ?? DB::table('branches')->where('id', 1)->value('id')
                ?? DB::table('branches')->value('id');

            if (!$mainBranchId) {
                $mainBranchId = DB::table('branches')->insertGetId([
                    'id' => 1,
                    'code' => 'BR-01',
                    'name' => 'الفرع الرئيسي',
                    'city' => 'طرابلس',
                    'address' => 'الإدارة العامة - طرابلس',
                    'status' => 'نشط',
                    'is_main_branch' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('departments')
                ->whereNull('branch_id')
                ->update(['branch_id' => $mainBranchId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'branch_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};