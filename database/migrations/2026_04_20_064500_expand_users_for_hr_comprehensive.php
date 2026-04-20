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
        Schema::table('users', function (Blueprint $table) {
            // البيانات الشخصية
            $table->string('full_name_quad', 255)->nullable()->after('name');
            $table->string('mother_name', 255)->nullable()->after('full_name_quad');
            $table->enum('gender', ['ذكر', 'أنثى'])->nullable()->after('mother_name');
            $table->date('birth_date')->nullable()->after('gender');
            $table->string('birth_place', 255)->nullable()->after('birth_date');
            $table->string('nationality', 100)->nullable()->after('birth_place');
            $table->string('social_status', 100)->nullable()->after('nationality');
            $table->string('qualification', 255)->nullable()->after('social_status');
            $table->string('blood_type', 10)->nullable()->after('qualification');
            $table->string('personal_phone', 50)->nullable()->after('blood_type');
            $table->string('guardian_phone', 50)->nullable()->after('personal_phone');
            $table->text('address')->nullable()->after('guardian_phone');

            // بيانات الوظيفة
            $table->string('financial_number', 100)->nullable()->after('job_title');
            $table->string('job_number', 100)->nullable()->after('financial_number');
            $table->string('bank_name', 191)->nullable()->after('job_number');
            $table->string('bank_branch', 191)->nullable()->after('bank_name');
            $table->string('account_number', 100)->nullable()->after('bank_branch');
            $table->date('start_date')->nullable()->after('account_number');
            $table->string('working_hours_from', 20)->nullable()->after('start_date');
            $table->string('working_hours_to', 20)->nullable()->after('working_hours_from');
            $table->string('working_days_from', 20)->nullable()->after('working_hours_to');
            $table->string('working_days_to', 20)->nullable()->after('working_days_from');
            $table->string('contract_type', 100)->nullable()->after('working_days_to');
            $table->text('contract_conditions')->nullable()->after('contract_type');

            // البيانات المالية
            $table->decimal('housing_allowance', 12, 2)->default(0)->after('salary');
            $table->decimal('transportation_allowance', 12, 2)->default(0)->after('housing_allowance');
            $table->decimal('communication_allowance', 12, 2)->default(0)->after('transportation_allowance');
            $table->decimal('fixed_bonuses', 12, 2)->default(0)->after('communication_allowance');
            $table->decimal('fixed_fines', 12, 2)->default(0)->after('fixed_bonuses');
            $table->decimal('hourly_leave_deduction', 12, 2)->default(0)->after('fixed_fines');
            $table->decimal('daily_leave_deduction', 12, 2)->default(0)->after('hourly_leave_deduction');

            // المستندات الإضافية (profile_photo_path, personal_id_proof_path, employment_contract_path already exist)
            $table->string('national_id_photo_path', 512)->nullable()->after('employment_contract_path');
            $table->string('identity_proof_path', 512)->nullable()->after('national_id_photo_path');
            $table->string('certified_stamp_path', 512)->nullable()->after('identity_proof_path');
            $table->string('approved_signature_path', 512)->nullable()->after('certified_stamp_path');
            $table->string('educational_certificate_path', 512)->nullable()->after('approved_signature_path');
            $table->string('health_certificate_path', 512)->nullable()->after('educational_certificate_path');
            $table->string('contract_conditions_photo_path', 512)->nullable()->after('health_certificate_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'full_name_quad', 'mother_name', 'gender', 'birth_date', 'birth_place', 
                'nationality', 'social_status', 'qualification', 'blood_type', 
                'personal_phone', 'guardian_phone', 'address',
                'financial_number', 'job_number', 'bank_name', 'bank_branch', 
                'account_number', 'start_date', 'working_hours_from', 'working_hours_to', 
                'working_days_from', 'working_days_to', 'contract_type', 'contract_conditions',
                'housing_allowance', 'transportation_allowance', 'communication_allowance', 
                'fixed_bonuses', 'fixed_fines', 'hourly_leave_deduction', 'daily_leave_deduction',
                'national_id_photo_path', 'identity_proof_path', 'certified_stamp_path', 
                'approved_signature_path', 'educational_certificate_path', 
                'health_certificate_path', 'contract_conditions_photo_path'
            ]);
        });
    }
};
