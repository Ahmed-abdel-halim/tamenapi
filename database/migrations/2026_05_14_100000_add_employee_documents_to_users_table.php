<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('passport_photo_path')->nullable()->after('contract_conditions_photo_path');
            $table->string('clearance_certificate_path')->nullable()->after('passport_photo_path');
            $table->string('experience_certificate_path')->nullable()->after('clearance_certificate_path');
            $table->string('work_commencement_order_path')->nullable()->after('experience_certificate_path');
            $table->string('resignation_letter_path')->nullable()->after('work_commencement_order_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'passport_photo_path',
                'clearance_certificate_path',
                'experience_certificate_path',
                'work_commencement_order_path',
                'resignation_letter_path',
            ]);
        });
    }
};
