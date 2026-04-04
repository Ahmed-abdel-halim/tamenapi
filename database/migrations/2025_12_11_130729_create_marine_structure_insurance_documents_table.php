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
        Schema::dropIfExists('marine_structure_engines');
        Schema::dropIfExists('marine_structure_insurance_documents');
        
        Schema::create('marine_structure_insurance_documents', function (Blueprint $table) {
            $table->id();
            // بيانات التأمين
            $table->string('insurance_number')->unique(); // MLMAR00001
            $table->timestamp('issue_date'); // تاريخ الإصدار مع الوقت
            $table->date('start_date'); // بداية التأمين
            $table->date('end_date'); // نهاية التأمين
            $table->enum('duration', ['سنة (365 يوم)', 'سنتين (730 يوم)'])->default('سنة (365 يوم)');
            $table->enum('structure_type', ['القوارب الشخصية والدراجات', 'الآلات والرافعات البحرية', 'قوارب الصيد']);
            $table->enum('license_type', ['خاص', 'صناعي', 'تجاري'])->nullable();
            $table->enum('license_purpose', ['قارب تجاري', 'قارب حرفي', 'قارب الترولة', 'قارب الشباك السينية', 'قارب الخيوط السنارية', 'قارب الصيد بالفخ'])->nullable();
            
            // بيانات المركب/الهيكل البحري
            $table->string('vessel_name')->nullable(); // اسم المركب/الهيكل
            $table->string('registration_code')->nullable(); // رمز ورقم التسجيل
            $table->date('registration_date')->nullable(); // تاريخ التسجيل
            $table->string('port')->nullable(); // الميناء أو المرفأ
            $table->foreignId('registration_authority_id')->nullable()->constrained('plates', 'id', 'marine_reg_auth_foreign')->onDelete('set null'); // الجهة المقيد بها (مثل plates في تأمين السيارات)
            $table->string('plate_number')->nullable(); // رقم اللوحة المعدنية
            $table->string('hull_number')->nullable(); // رقم الهيكل
            $table->string('manufacturing_material')->nullable(); // نوع مواد التصنيع
            $table->decimal('length', 10, 2)->nullable(); // الطول
            $table->decimal('width', 10, 2)->nullable(); // العرض
            $table->decimal('depth', 10, 2)->nullable(); // العمق
            $table->integer('manufacturing_year')->nullable(); // تاريخ الصنع (1960-2026)
            $table->string('manufacturing_country')->nullable(); // مكان الصنع
            $table->string('color')->nullable(); // اللون
            $table->decimal('fuel_tank_capacity', 10, 2)->nullable(); // سعة خزان الوقود
            $table->integer('passenger_count')->nullable(); // عدد الركاب
            $table->decimal('load_capacity', 10, 2)->nullable(); // الحمولة بالطن
            
            // بيانات المؤمن له
            $table->string('insured_name')->nullable(); // اسم المؤمن له
            $table->string('phone')->nullable(); // رقم الهاتف
            $table->string('license_number')->nullable(); // رقم الرخصة
            
            // القيمة المالية
            $table->decimal('premium', 10, 3)->default(0); // القسط المقرر
            $table->decimal('tax', 10, 3)->default(1.000); // الضريبة
            $table->decimal('stamp', 10, 3)->default(0.500); // الدمغة
            $table->decimal('issue_fees', 10, 3)->default(2.000); // مصاريف الإصدار
            $table->decimal('supervision_fees', 10, 3)->default(0.500); // رسوم الإشراف
            $table->decimal('total', 10, 3)->default(0); // الإجمالي
            
            $table->timestamps();
        });

        Schema::create('marine_structure_engines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marine_structure_insurance_document_id')->constrained('marine_structure_insurance_documents', 'id', 'marine_eng_doc_foreign')->onDelete('cascade');
            $table->enum('engine_type', ['main', 'auxiliary'])->default('main'); // محرك رئيسي أو مساعد
            $table->string('engine_model')->nullable(); // نوع المحرك
            $table->enum('fuel_type', ['بنزين Gasoline', 'ديزل Diesel', 'كهرباء', 'غاز طبيعي', 'هيدروجين'])->nullable();
            $table->string('engine_number')->nullable(); // رقم المحرك
            $table->string('manufacturing_country')->nullable(); // مكان الصنع
            $table->decimal('horsepower', 10, 2)->nullable(); // القوة بالحصان
            $table->date('installation_date')->nullable(); // تاريخ التركيب
            $table->integer('cylinders_count')->nullable(); // عدد الإسطوانات
            $table->enum('installation_type', ['داخلي', 'خارجي'])->nullable(); // تركيب المحرك
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marine_structure_engines');
        Schema::dropIfExists('marine_structure_insurance_documents');
    }
};
