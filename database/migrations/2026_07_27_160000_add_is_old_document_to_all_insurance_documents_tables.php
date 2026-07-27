<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'insurance_documents',
            'international_insurance_documents',
            'travel_insurance_documents',
            'resident_insurance_documents',
            'marine_structure_insurance_documents',
            'professional_liability_insurance_documents',
            'personal_accident_insurance_documents',
            'cargo_insurance_documents',
            'cash_in_transit_insurance_documents',
            'school_student_insurance_documents'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'is_old_document')) {
                Schema::table($table, function (Blueprint $tableGroup) {
                    $tableGroup->boolean('is_old_document')->default(false)->after('id');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'insurance_documents',
            'international_insurance_documents',
            'travel_insurance_documents',
            'resident_insurance_documents',
            'marine_structure_insurance_documents',
            'professional_liability_insurance_documents',
            'personal_accident_insurance_documents',
            'cargo_insurance_documents',
            'cash_in_transit_insurance_documents',
            'school_student_insurance_documents'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'is_old_document')) {
                Schema::table($table, function (Blueprint $tableGroup) {
                    $tableGroup->dropColumn('is_old_document');
                });
            }
        }
    }
};
