<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * يضيف حقول التكامل مع نظام هيئة الإشراف على التأمين (EIDC)
     * للتأمين الإجباري على السيارات فقط
     */
    public function up(): void
    {
        Schema::table('insurance_documents', function (Blueprint $table) {
            // بيانات المؤمن عليه المطلوبة من الهيئة
            $table->string('nid_passport', 50)->nullable()->after('driving_license_number')
                ->comment('رقم الهوية الوطنية أو جواز السفر - مطلوب من هيئة الإشراف');

            // بيانات تصنيف المركبة في نظام الهيئة
            $table->string('eidc_vehicle_type_id')->nullable()->after('nid_passport')
                ->comment('معرف نوع المركبة في نظام الهيئة (typeVechicleId)');
            $table->string('eidc_vehicle_spec_id')->nullable()->after('eidc_vehicle_type_id')
                ->comment('معرف مواصفة المركبة في نظام الهيئة (typeVechicle2Id)');
            $table->string('eidc_vehicle_detail_id')->nullable()->after('eidc_vehicle_spec_id')
                ->comment('معرف تفاصيل المركبة في نظام الهيئة (typeVechicle3Id) - اختياري');

            // نتيجة الإصدار من الهيئة
            $table->string('eidc_policy_id')->nullable()->after('eidc_vehicle_detail_id')
                ->comment('معرف الوثيقة في نظام الهيئة (policyId)');
            $table->string('eidc_transaction_code')->nullable()->after('eidc_policy_id')
                ->comment('كود المعاملة من الهيئة (transactionCode)');
            $table->text('eidc_pdf_url')->nullable()->after('eidc_transaction_code')
                ->comment('رابط PDF الوثيقة من نظام الهيئة');

            // حالة المزامنة مع الهيئة
            $table->enum('eidc_sync_status', ['pending', 'synced', 'failed', 'cancelled'])
                ->nullable()->after('eidc_pdf_url')
                ->comment('حالة التزامن مع نظام الهيئة');
            $table->text('eidc_error')->nullable()->after('eidc_sync_status')
                ->comment('رسالة الخطأ من الهيئة إن وجدت');
            $table->timestamp('eidc_synced_at')->nullable()->after('eidc_error')
                ->comment('تاريخ ووقت التزامن مع الهيئة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_documents', function (Blueprint $table) {
            $table->dropColumn([
                'nid_passport',
                'eidc_vehicle_type_id',
                'eidc_vehicle_spec_id',
                'eidc_vehicle_detail_id',
                'eidc_policy_id',
                'eidc_transaction_code',
                'eidc_pdf_url',
                'eidc_sync_status',
                'eidc_error',
                'eidc_synced_at',
            ]);
        });
    }
};
