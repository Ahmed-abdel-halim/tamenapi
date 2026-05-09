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
            if (!Schema::hasColumn('users', 'tax_file_number')) {
                $table->string('tax_file_number')->nullable()->after('job_number')->comment('رقم الملف الضريبي');
            }
            if (!Schema::hasColumn('users', 'social_security_file_number')) {
                $table->string('social_security_file_number')->nullable()->after('tax_file_number')->comment('رقم الملف الضماني');
            }
            if (!Schema::hasColumn('users', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date')->comment('تاريخ انتهاء العمل');
            }
            if (!Schema::hasColumn('users', 'apply_tax')) {
                $table->boolean('apply_tax')->default(true)->after('tax_percentage')->comment('هل تنطبق الضرائب؟');
            }
            if (!Schema::hasColumn('users', 'apply_social_security')) {
                $table->boolean('apply_social_security')->default(true)->after('social_security_percentage')->comment('هل ينطبق الضمان؟');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tax_file_number', 'social_security_file_number', 'end_date', 'apply_tax', 'apply_social_security']);
        });
    }
};
