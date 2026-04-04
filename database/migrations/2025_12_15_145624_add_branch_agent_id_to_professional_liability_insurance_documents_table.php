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
        Schema::table('professional_liability_insurance_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('professional_liability_insurance_documents', 'branch_agent_id')) {
                $table->foreignId('branch_agent_id')->nullable()->after('total')->constrained('branches_agents')->onDelete('set null')->name('prof_liab_ins_doc_branch_agent_id_foreign');
            } else {
                // إذا كان العمود موجوداً، أضف الـ foreign key فقط
                $table->foreign('branch_agent_id', 'prof_liab_ins_doc_branch_agent_id_foreign')
                    ->references('id')
                    ->on('branches_agents')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('professional_liability_insurance_documents', function (Blueprint $table) {
            $table->dropForeign(['branch_agent_id']);
            $table->dropColumn('branch_agent_id');
        });
    }
};
