<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;

return new class extends Migration
{
    /**
     * Target tables across Financial Accounting, Claims, Requests, POS and Custodies
     */
    protected array $targetTables = [
        'claims',
        'expenses',
        'payment_vouchers',
        'rental_vouchers',
        'agent_transfers',
        'treasury_transactions',
        'bank_transactions',
        'monthly_account_closures',
        'financial_archives',
        'employee_payrolls',
        'document_requests',
        'compulsory_document_requests',
        'agent_requests',
        'agency_cancellations',
        'employee_requests',
        'profile_update_requests',
        'custodies',
        'custody_movements',
        'pos_machines',
        'pos_transactions',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add branch_id column to each table if not already present
        foreach ($this->targetTables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                    $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                });
            }
        }

        // 2. Identify the main branch (ID 1 / is_main_branch = true)
        $mainBranch = Branch::where('is_main_branch', true)->first()
            ?? Branch::where('id', 1)->first()
            ?? Branch::first();

        if ($mainBranch) {
            $mainBranchId = $mainBranch->id;

            // 3. Assign existing records to the main branch
            foreach ($this->targetTables as $tableName) {
                if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                    DB::table($tableName)
                        ->whereNull('branch_id')
                        ->update(['branch_id' => $mainBranchId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->targetTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};
