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
        if (!Schema::hasColumn('resident_insurance_passengers', 'occupation')) {
            Schema::table('resident_insurance_passengers', function (Blueprint $table) {
                $table->string('occupation')->nullable()->after('nationality');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_insurance_passengers', function (Blueprint $table) {
            $table->dropColumn('occupation');
        });
    }
};
