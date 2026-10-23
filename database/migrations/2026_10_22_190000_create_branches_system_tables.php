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
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->nullable();
                $table->string('name');
                $table->string('city')->nullable();
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('manager_name')->nullable();
                $table->string('manager_phone')->nullable();
                $table->string('status')->default('نشط'); // نشط, موقف
                $table->boolean('is_main_branch')->default(false);
                $table->string('commercial_registration')->nullable();
                $table->string('tax_number')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Add branch_id to users
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'branch_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('branch_agent_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            });
        }

        // Add branch_id to all insurance document tables
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

        foreach ($documentTables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('branch_agent_id');
                    $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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

        foreach ($documentTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                });
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'branch_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }

        Schema::dropIfExists('branches');
    }
};
