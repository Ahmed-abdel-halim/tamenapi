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
        Schema::table('travel_insurance_documents', function (Blueprint $table) {
            $table->string('residence_type')->nullable()->after('geographic_area');
            $table->integer('residence_duration')->nullable()->after('residence_type'); // الأيام
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_insurance_documents', function (Blueprint $table) {
            $table->dropColumn(['residence_type', 'residence_duration']);
        });
    }
};

