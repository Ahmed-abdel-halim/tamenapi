<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->string('policy_type')->unique();
            $table->string('display_name');
            $table->integer('points_reward')->default(10);
            $table->timestamps();
        });

        // Seed initial values
        $defaultSettings = [
            ['policy_type' => 'InsuranceDocument', 'display_name' => 'تأمين سيارات', 'points_reward' => 10],
            ['policy_type' => 'InternationalInsuranceDocument', 'display_name' => 'تأمين سيارات دولي', 'points_reward' => 20],
            ['policy_type' => 'TravelInsuranceDocument', 'display_name' => 'تأمين مسافرين', 'points_reward' => 15],
            ['policy_type' => 'ResidentInsuranceDocument', 'display_name' => 'تأمين الوافدين', 'points_reward' => 15],
            ['policy_type' => 'MarineStructureInsuranceDocument', 'display_name' => 'تأمين الهياكل البحرية', 'points_reward' => 30],
            ['policy_type' => 'ProfessionalLiabilityInsuranceDocument', 'display_name' => 'تأمين المسؤولية المهنية', 'points_reward' => 25],
            ['policy_type' => 'PersonalAccidentInsuranceDocument', 'display_name' => 'تأمين الحوادث الشخصية', 'points_reward' => 20],
            ['policy_type' => 'SchoolStudentInsuranceDocument', 'display_name' => 'تأمين طلبة المدارس', 'points_reward' => 10],
            ['policy_type' => 'CargoInsuranceDocument', 'display_name' => 'تأمين شحن البضائع', 'points_reward' => 25],
            ['policy_type' => 'CashInTransitInsuranceDocument', 'display_name' => 'تأمين نقل النقدية', 'points_reward' => 25],
        ];

        foreach ($defaultSettings as $setting) {
            DB::table('agent_loyalty_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_loyalty_settings');
    }
};
