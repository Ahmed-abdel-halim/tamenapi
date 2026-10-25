<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Branch;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Get or create the main branch ID (either is_main_branch = true, or ID 1, or the first branch)
        $mainBranch = Branch::where('is_main_branch', true)->first()
            ?? Branch::where('id', 1)->first()
            ?? Branch::first();

        if (!$mainBranch) {
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
        } else {
            $mainBranchId = $mainBranch->id;
        }

        // 2. Assign all existing agents with NULL branch_id to main branch
        if (Schema::hasTable('branches_agents') && Schema::hasColumn('branches_agents', 'branch_id')) {
            DB::table('branches_agents')
                ->whereNull('branch_id')
                ->update(['branch_id' => $mainBranchId]);
        }

        // 3. Assign all users with NULL branch_id (except Super Admins who remain NULL for global access)
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'branch_id')) {
            DB::table('users')
                ->whereNull('branch_id')
                ->where(function ($query) {
                    $query->whereNull('is_admin')
                          ->orWhere('is_admin', false);
                })
                ->update(['branch_id' => $mainBranchId]);
        }

        // 4. Assign all historical insurance documents to main branch
        $documentTables = [
            'insurance_documents',
            'international_insurance_documents',
            'travel_insurance_documents',
            'resident_insurance_documents',
            'marine_structure_insurance_documents',
            'professional_liability_insurance_documents',
            'personal_accident_insurance_documents',
            'school_student_insurance_documents',
            'cash_in_transit_insurance_documents',
            'cargo_insurance_documents',
        ];

        foreach ($documentTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                DB::table($table)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $mainBranchId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op to preserve assignments
    }
};
