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
        $tables = [
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
            'travel_insurance_passengers',
            'resident_insurance_passengers',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'whatsapp_number')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->string('whatsapp_number')->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
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
            'school_student_insurance_documents',
            'cash_in_transit_insurance_documents',
            'cargo_insurance_documents',
            'travel_insurance_passengers',
            'resident_insurance_passengers',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'whatsapp_number')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('whatsapp_number');
                });
            }
        }
    }
};
