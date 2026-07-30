<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة حقول الإلغاء لجميع جداول وثائق التأمين
     * الوثيقة الملغية تُعتبر غير موجودة في الحسابات لكنها محفوظة للأرشيف
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
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'is_canceled')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->boolean('is_canceled')->default(false);
                    $table->timestamp('canceled_at')->nullable();
                    $table->unsignedBigInteger('canceled_by')->nullable();
                    $table->text('cancel_reason')->nullable();
                    $table->index('is_canceled');
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
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'is_canceled')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn(['is_canceled', 'canceled_at', 'canceled_by', 'cancel_reason']);
                });
            }
        }
    }
};
