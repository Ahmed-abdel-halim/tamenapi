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
        Schema::create('insurance_documents', function (Blueprint $table) {
            $table->id();
            // نوع التأمين
            $table->enum('insurance_type', ['تأمين إجباري سيارات', 'تأمين سيارة جمرك', 'تأمين طرف ثالث سيارات', 'تأمين سيارات أجنبية']);
            // بيانات التأمين
            $table->string('insurance_number')->unique(); // رقم التأمين (تلقائي)
            $table->timestamp('issue_date'); // تاريخ الإصدار (تلقائي)
            $table->foreignId('plate_id')->nullable()->constrained('plates')->onDelete('set null'); // الجهة المقيد بها
            $table->date('start_date'); // بداية التأمين
            $table->date('end_date'); // نهاية التأمين
            $table->enum('duration', ['سنة', 'سنتين'])->default('سنة'); // مدة التأمين
            // بيانات المركبة
            $table->string('chassis_number')->nullable(); // رقم الهيكل
            $table->string('plate_number_manual')->nullable(); // رقم اللوحة المعدنية (يدوي)
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types')->onDelete('set null'); // نوع السيارة
            $table->string('color')->nullable(); // اللون
            $table->integer('year')->nullable(); // السنة
            $table->string('manufacturing_country')->nullable(); // بلد الصنع
            $table->enum('fuel_type', ['بنزين/Gasoline', 'ديزل/Diesel', 'كهرباء/Electric', 'غاز طبيعي/CNG', 'هيدروجين/Hydrogen'])->nullable(); // نوع الوقود
            $table->enum('license_purpose', ['خاصة/Private', 'عامة/Public', 'نقل/Transport', 'زراعي/Agricultural', 'صناعي/Industrial'])->nullable(); // الغرض من الترخيص
            $table->enum('engine_power', ['أقل من (16) حصان', 'من (17) الي (30) حصان', 'أكثر من (30) حصان', 'سيارة تجارية', 'سيارة تعليم قيادة', 'سيارة اسعاف'])->nullable(); // قوة المحرك
            $table->integer('authorized_passengers')->nullable(); // الركاب المصرح بهم (1-100)
            $table->decimal('load_capacity', 8, 2)->nullable(); // الحمولة بالطن (1-100)
            // بيانات المؤمن له
            $table->string('insured_name')->nullable(); // اسم المؤمن
            $table->string('phone')->nullable(); // رقم الهاتف
            $table->string('driving_license_number')->nullable(); // رقم رخصة القيادة
            // القيمة المالية
            $table->decimal('premium', 10, 2)->default(0); // القسط
            $table->decimal('tax', 10, 2)->default(1.000); // الضريبة (ثابتة)
            $table->decimal('stamp', 10, 2)->default(0.500); // الدمغة (ثابتة)
            $table->decimal('issue_fees', 10, 2)->default(2.000); // مصاريف الإصدار (ثابتة)
            $table->decimal('supervision_fees', 10, 2)->default(0.500); // رسوم الإشراف (ثابتة)
            $table->decimal('total', 10, 2)->default(0); // الإجمالي
            $table->enum('print_type', ['A5', 'A4'])->default('A4'); // نوع الطباعة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_documents');
    }
};
