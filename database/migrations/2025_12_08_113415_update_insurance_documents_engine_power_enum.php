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
        Schema::table('insurance_documents', function (Blueprint $table) {
            // تغيير enum إلى string لدعم جميع القيم الجديدة
            $table->string('engine_power')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_documents', function (Blueprint $table) {
            // إعادة enum الأصلي
            $table->enum('engine_power', ['أقل من (16) حصان', 'من (17) الي (30) حصان', 'أكثر من (30) حصان', 'سيارة تجارية', 'سيارة تعليم قيادة', 'سيارة اسعاف'])->nullable()->change();
        });
    }
};
