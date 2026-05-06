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
            $table->string('email')->nullable()->after('phone');
            $table->string('nationality')->nullable()->after('nid_passport');
            $table->string('engine_number')->nullable()->after('engine_power');
            $table->string('engine_cc')->nullable()->after('engine_number');
            $table->string('vehicle_weight')->nullable()->after('load_capacity');
            $table->text('notes')->nullable()->after('eidc_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_documents', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'nationality',
                'engine_number',
                'engine_cc',
                'vehicle_weight',
                'notes'
            ]);
        });
    }
};
