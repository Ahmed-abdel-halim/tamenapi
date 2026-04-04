<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('international_insurance_documents', function (Blueprint $table) {
            // تغيير vehicle_nationality من enum إلى string
            DB::statement("ALTER TABLE international_insurance_documents MODIFY vehicle_nationality VARCHAR(255) NULL");
            
            // تغيير visited_country من enum إلى string
            DB::statement("ALTER TABLE international_insurance_documents MODIFY visited_country VARCHAR(255) NULL");
            
            // إضافة الأعمدة الجديدة للـ external IDs
            $table->integer('external_car_id')->nullable()->after('vehicle_type_id');
            $table->integer('external_vehicle_nationality_id')->nullable()->after('vehicle_nationality');
            $table->integer('external_country_id')->nullable()->after('visited_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('international_insurance_documents', function (Blueprint $table) {
            // حذف الأعمدة الجديدة
            $table->dropColumn(['external_car_id', 'external_vehicle_nationality_id', 'external_country_id']);
            
            // إعادة vehicle_nationality إلى enum (اختياري - يمكن تركها كـ string)
            // DB::statement("ALTER TABLE international_insurance_documents MODIFY vehicle_nationality ENUM('ليبية- LBY') NULL");
            
            // إعادة visited_country إلى enum (اختياري - يمكن تركها كـ string)
            // DB::statement("ALTER TABLE international_insurance_documents MODIFY visited_country ENUM('تونس', 'الجزائر', 'تونس و الجزائر', 'مصر') NULL");
        });
    }
};
