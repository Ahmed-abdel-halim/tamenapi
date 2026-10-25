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
        $tables = [
            'fixed_custodies',
            'store_items',
            'inventory_stocks',
            'pos_custody_movements',
            'commissions',
            'union_balance_purchases',
            'company_documents',
            'mail_documents',
            'insurance_supervision_payments',
            'insurance_ownership_transfers',
            'employee_salary_histories',
        ];

        // Ensure main branch exists as fallback
        $mainBranch = DB::table('branches')->where('is_main_branch', true)->first()
            ?? DB::table('branches')->where('id', 1)->first()
            ?? DB::table('branches')->first();

        if (!$mainBranch) {
            $fallbackBranchId = DB::table('branches')->insertGetId([
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
        } else {
            $fallbackBranchId = $mainBranch->id;
        }

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                if (!Schema::hasColumn($table, 'branch_id')) {
                    Schema::table($table, function (Blueprint $tableBlueprint) {
                        $tableBlueprint->foreignId('branch_id')
                            ->nullable()
                            ->after('id')
                            ->constrained('branches')
                            ->onDelete('set null');
                    });
                }

                // Backfill existing rows with main branch ID
                if ($fallbackBranchId) {
                    DB::table($table)
                        ->whereNull('branch_id')
                        ->update(['branch_id' => $fallbackBranchId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'fixed_custodies',
            'store_items',
            'inventory_stocks',
            'pos_custody_movements',
            'commissions',
            'union_balance_purchases',
            'company_documents',
            'mail_documents',
            'insurance_supervision_payments',
            'insurance_ownership_transfers',
            'employee_salary_histories',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropForeign(['branch_id']);
                    $tableBlueprint->dropColumn('branch_id');
                });
            }
        }
    }
};
