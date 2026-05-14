<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches_agents', function (Blueprint $table) {
            $table->string('passport_photo')->nullable()->after('contract_photo');
            $table->string('clearance_certificate')->nullable()->after('passport_photo');
            $table->string('non_bankruptcy_certificate')->nullable()->after('clearance_certificate');
            $table->string('experience_certificate')->nullable()->after('non_bankruptcy_certificate');
            $table->string('non_employment_certificate')->nullable()->after('experience_certificate');
            $table->string('tb_health_certificate')->nullable()->after('non_employment_certificate');
            $table->string('academic_qualification')->nullable()->after('tb_health_certificate');
            $table->string('activity_license')->nullable()->after('academic_qualification');
        });
    }

    public function down(): void
    {
        Schema::table('branches_agents', function (Blueprint $table) {
            $table->dropColumn([
                'passport_photo',
                'clearance_certificate',
                'non_bankruptcy_certificate',
                'experience_certificate',
                'non_employment_certificate',
                'tb_health_certificate',
                'academic_qualification',
                'activity_license',
            ]);
        });
    }
};
