<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->integer('fatalities_count')->nullable()->after('has_fatalities');
            $table->text('damaged_vehicle_details')->nullable()->after('damaged_vehicle_model');
            $table->text('damaged_person_details')->nullable()->after('damaged_person_name');
            $table->string('victim_insurance_coverage')->nullable()->after('victim_insurance_type');
            $table->json('damage_costs')->nullable();
            $table->json('damage_cost_invoices')->nullable();
        });
    }

    public function down()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'fatalities_count',
                'damaged_vehicle_details',
                'damaged_person_details',
                'victim_insurance_coverage',
                'damage_costs',
                'damage_cost_invoices'
            ]);
        });
    }
};
