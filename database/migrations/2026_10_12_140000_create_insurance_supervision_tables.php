<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('insurance_supervision_settings')) {
            Schema::create('insurance_supervision_settings', function (Blueprint $table) {
                $table->id();
                $table->string('insurance_type_key')->unique();
                $table->string('insurance_type_name');
                $table->decimal('fee_rate_percentage', 6, 2)->default(1.00);
                $table->decimal('fixed_fee_amount', 10, 2)->default(0.00);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            $defaultSettings = [
                ['insurance_type_key' => 'vehicle', 'insurance_type_name' => 'تأمين المركبات الآلية (إجباري / شامل)', 'fee_rate_percentage' => 1.00, 'fixed_fee_amount' => 0.00],
                ['insurance_type_key' => 'international', 'insurance_type_name' => 'تأمين البطاقة البرتقالية (دولي)', 'fee_rate_percentage' => 1.00, 'fixed_fee_amount' => 0.00],
                ['insurance_type_key' => 'marine', 'insurance_type_name' => 'التأمين البحري وأجسام السفن', 'fee_rate_percentage' => 1.00, 'fixed_fee_amount' => 0.00],
                ['insurance_type_key' => 'travel', 'insurance_type_name' => 'تأمين السفر بالخارج', 'fee_rate_percentage' => 1.00, 'fixed_fee_amount' => 0.00],
                ['insurance_type_key' => 'resident', 'insurance_type_name' => 'تأمين الرعاية الصحية للمقيمين والأجانب', 'fee_rate_percentage' => 1.00, 'fixed_fee_amount' => 0.00],
                ['insurance_type_key' => 'personal_accident', 'insurance_type_name' => 'تأمين الحوادث الشخصية', 'fee_rate_percentage' => 1.00, 'fixed_fee_amount' => 0.00],
                ['insurance_type_key' => 'professional_liability', 'insurance_type_name' => 'تأمين المسؤولية المهنية والمدنية', 'fee_rate_percentage' => 1.00, 'fixed_fee_amount' => 0.00],
                ['insurance_type_key' => 'cargo', 'insurance_type_name' => 'تأمين نقل البضائع', 'fee_rate_percentage' => 1.00, 'fixed_fee_amount' => 0.00],
                ['insurance_type_key' => 'cash_in_transit', 'insurance_type_name' => 'تأمين نقل الأموال', 'fee_rate_percentage' => 1.00, 'fixed_fee_amount' => 0.00],
                ['insurance_type_key' => 'school_student', 'insurance_type_name' => 'تأمين طلبة المدارس', 'fee_rate_percentage' => 1.00, 'fixed_fee_amount' => 0.00],
            ];

            $now = now();
            foreach ($defaultSettings as $setting) {
                DB::table('insurance_supervision_settings')->insert(array_merge($setting, [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        if (!Schema::hasTable('insurance_supervision_payments')) {
            Schema::create('insurance_supervision_payments', function (Blueprint $table) {
                $table->id();
                $table->string('voucher_number')->nullable()->index();
                $table->date('payment_date')->index();
                $table->decimal('amount', 15, 2);
                $table->string('payment_method')->default('bank_transfer');
                $table->string('bank_name')->nullable();
                $table->string('check_number')->nullable();
                $table->date('period_from')->nullable();
                $table->date('period_to')->nullable();
                $table->string('receipt_image')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_supervision_payments');
        Schema::dropIfExists('insurance_supervision_settings');
    }
};
