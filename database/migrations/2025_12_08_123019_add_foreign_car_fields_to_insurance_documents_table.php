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
            $table->string('foreign_car_country')->nullable()->after('third_party_purpose');
            $table->string('foreign_car_purpose')->nullable()->after('foreign_car_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_documents', function (Blueprint $table) {
            $table->dropColumn(['foreign_car_country', 'foreign_car_purpose']);
        });
    }
};
