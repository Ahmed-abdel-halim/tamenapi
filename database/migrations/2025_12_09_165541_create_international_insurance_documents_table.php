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
        Schema::create('international_insurance_documents', function (Blueprint $table) {
            $table->id();
            // رقم الوثيقة (LBY0001)
            $table->string('document_number')->unique();
            // بيانات المؤمن
            $table->string('insured_name'); // اسم المؤمن
            $table->string('insured_address')->nullable(); // العنوان
            $table->string('phone')->nullable(); // الهاتف
            $table->string('chassis_number')->nullable(); // رقم الهيكل
            $table->string('plate_number')->nullable(); // رقم اللوحة المعدنية
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types')->onDelete('set null'); // نوع السيارة
            $table->integer('year')->nullable(); // السنة (1960-2026)
            $table->enum('vehicle_nationality', ['ليبية- LBY'])->nullable(); // جنسية المركبة
            $table->enum('visited_country', ['تونس', 'الجزائر', 'تونس و الجزائر', 'مصر'])->nullable(); // البلد المزار
            // مدة التأمين
            $table->date('start_date'); // من يوم
            $table->integer('number_of_days'); // عدد الأيام
            $table->date('end_date'); // إلي يوم
            // احتساب القسط
            $table->enum('item_type', [
                'سيارات خاصة ملاكي',
                'دراجة نارية',
                'سيارة تعليم قيادة',
                'سيارة اسعاف',
                'سيارة نقل الموتى',
                'مقطورة',
                'السيارات التجارية',
                'الجرارات',
                'سيارات نقل بضائع',
                'سيارات الركوبة الحافلات'
            ])->nullable(); // البند
            $table->integer('number_of_countries')->default(1); // عدد الدول (ثابت = 1)
            $table->decimal('daily_premium', 10, 3)->default(0); // القسط اليومي (7 أو 8)
            // القيمة المالية
            $table->decimal('premium', 10, 3)->default(0); // القسط (البند × عدد الأيام)
            $table->decimal('tax', 10, 3)->default(0); // الضريبة (0.5 أو 1)
            $table->decimal('supervision_fees', 10, 3)->default(0); // الإشراف (0.245 أو 0.280)
            $table->decimal('issue_fees', 10, 3)->default(10.000); // الإصدار (10.000)
            $table->decimal('stamp', 10, 3)->default(0.250); // دمغة المحررات (0.250)
            $table->decimal('total', 10, 3)->default(0); // الإجمالي
            $table->timestamp('issue_date')->useCurrent(); // تاريخ الإصدار
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('international_insurance_documents');
    }
};
