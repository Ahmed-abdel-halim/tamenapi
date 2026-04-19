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
        if (!Schema::hasTable('professional_liability_insurance_documents')) {
            Schema::create('professional_liability_insurance_documents', function (Blueprint $table) {
                $table->id();
                // بيانات التأمين
                $table->string('insurance_number'); // BKPRL00001
                $table->timestamp('issue_date'); // تاريخ الإصدار
                $table->date('start_date'); // بداية التأمين
                $table->date('end_date'); // نهاية التأمين
                $table->enum('duration', ['سنة (365 يوم)'])->default('سنة (365 يوم)'); // مدة التأمين
                // بيانات المسافر
                $table->enum('contract_relation', ['نفسه', 'جهة العمل'])->default('نفسه'); // صلة التعاقد في المؤمن له
                $table->string('contractor_name')->nullable(); // اسم المتعاقد
                $table->string('insured_name'); // اسم المؤمن له
                $table->date('birth_date')->nullable(); // تاريخ الميلاد
                $table->integer('age')->nullable(); // العمر (تلقائي)
                $table->string('phone')->nullable(); // رقم الهاتف
                $table->string('workplace')->nullable(); // مكان العمل
                $table->enum('gender', ['ذكر Male', 'انثى Female'])->nullable(); // الجنس
                $table->string('nationality')->nullable(); // الجنسية
                $table->string('profession')->nullable(); // المهنة
                $table->enum('marital_status', ['أعزب/عزباء', 'متزوج/متزوجة', 'مطلق/مطلقة'])->nullable(); // الحالة الإجتماعية
                // القيمة المالية
                $table->decimal('premium', 10, 3)->default(210.000); // القسط المقرر
                $table->decimal('tax', 10, 3)->default(2.500); // الضريبة
                $table->decimal('stamp', 10, 3)->default(0.500); // الدمغة
                $table->decimal('issue_fees', 10, 3)->default(10.000); // مصاريف الإصدار
                $table->decimal('supervision_fees', 10, 3)->default(1.050); // رسوم الإشراف
                $table->decimal('total', 10, 3)->default(0); // الإجمالي
                $table->timestamps();
                
                // إضافة unique constraint مع اسم أقصر
                $table->unique('insurance_number', 'pli_insurance_number_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_liability_insurance_documents');
    }
};
