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
            // تغيير enum إلى string لدعم القيم الجديدة
            $table->string('duration')->default('سنة')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_documents', function (Blueprint $table) {
            // إعادة enum الأصلي
            $table->enum('duration', ['سنة', 'سنتين'])->default('سنة')->change();
        });
    }
};
