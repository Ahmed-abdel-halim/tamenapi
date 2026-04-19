<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('national_id_number', 64)->nullable()->after('salary');
            $table->string('job_title', 191)->nullable()->after('national_id_number');
            $table->string('profile_photo_path', 512)->nullable()->after('job_title');
            $table->string('personal_id_proof_path', 512)->nullable()->after('profile_photo_path');
            $table->string('employment_contract_path', 512)->nullable()->after('personal_id_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'national_id_number',
                'job_title',
                'profile_photo_path',
                'personal_id_proof_path',
                'employment_contract_path',
            ]);
        });
    }
};
