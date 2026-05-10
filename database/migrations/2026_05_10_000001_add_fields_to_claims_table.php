<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('claims', function (Blueprint $table) {
            // Basic claim extra fields
            $table->string('accident_location')->nullable()->after('accident_date');
            $table->time('accident_time')->nullable()->after('accident_location');
            $table->boolean('has_fatalities')->default(false)->after('accident_time');

            // Claimant extra
            $table->string('claimant_check_number')->nullable()->after('phone_number');

            // Driver info
            $table->string('driver_name')->nullable()->after('claimant_check_number');
            $table->string('driver_nationality')->nullable()->after('driver_name');
            $table->string('driver_id_number')->nullable()->after('driver_nationality');
            $table->string('driver_license_number')->nullable()->after('driver_id_number');
            $table->date('driver_license_issue_date')->nullable()->after('driver_license_number');
            $table->date('driver_license_expiry_date')->nullable()->after('driver_license_issue_date');
            $table->string('driver_photo')->nullable()->after('driver_license_expiry_date');
            $table->string('driver_license_photo')->nullable()->after('driver_photo');

            // Damaged body info
            $table->string('damaged_body_type')->nullable()->after('driver_license_photo'); // سيارة / شخص / مبنى

            // Damaged vehicle info
            $table->string('damaged_vehicle_model')->nullable()->after('damaged_body_type');
            $table->string('damaged_vehicle_plate')->nullable()->after('damaged_vehicle_model');
            $table->decimal('damaged_vehicle_amount', 15, 3)->nullable()->after('damaged_vehicle_plate');
            $table->string('damaged_vehicle_repair_shop')->nullable()->after('damaged_vehicle_amount');
            $table->json('damaged_vehicle_photos')->nullable()->after('damaged_vehicle_repair_shop');

            // Damaged person info
            $table->string('damaged_person_name')->nullable()->after('damaged_vehicle_photos');
            $table->decimal('damaged_person_amount', 15, 3)->nullable()->after('damaged_person_name');
            $table->json('damaged_person_photos')->nullable()->after('damaged_person_amount');

            // Damaged building info
            $table->string('damaged_building_description')->nullable()->after('damaged_person_photos');
            $table->decimal('damaged_building_amount', 15, 3)->nullable()->after('damaged_building_description');
            $table->json('damaged_building_photos')->nullable()->after('damaged_building_amount');

            // Victim's insurance document info
            $table->string('victim_insurance_company')->nullable()->after('damaged_building_photos');
            $table->string('victim_insurance_number')->nullable()->after('victim_insurance_company');
            $table->string('victim_insurance_type')->nullable()->after('victim_insurance_number');
            $table->date('victim_insurance_issue_date')->nullable()->after('victim_insurance_type');
            $table->date('victim_insurance_expiry_date')->nullable()->after('victim_insurance_issue_date');
            $table->string('victim_insurance_photo')->nullable()->after('victim_insurance_expiry_date');

            // Damage assessor
            $table->string('assessor_name')->nullable()->after('victim_insurance_photo');
            $table->string('assessor_phone')->nullable()->after('assessor_name');
            $table->date('assessor_date')->nullable()->after('assessor_phone');
            $table->decimal('assessor_amount_dinar', 15, 3)->nullable()->after('assessor_date');
            $table->decimal('assessor_amount_dollar', 15, 3)->nullable()->after('assessor_amount_dinar');
            $table->string('assessor_report_photo')->nullable()->after('assessor_amount_dollar');

            // Administrative number
            $table->string('admin_number')->nullable()->after('reference_number');
        });
    }

    public function down()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'accident_location', 'accident_time', 'has_fatalities',
                'claimant_check_number',
                'driver_name', 'driver_nationality', 'driver_id_number',
                'driver_license_number', 'driver_license_issue_date', 'driver_license_expiry_date',
                'driver_photo', 'driver_license_photo',
                'damaged_body_type',
                'damaged_vehicle_model', 'damaged_vehicle_plate', 'damaged_vehicle_amount',
                'damaged_vehicle_repair_shop', 'damaged_vehicle_photos',
                'damaged_person_name', 'damaged_person_amount', 'damaged_person_photos',
                'damaged_building_description', 'damaged_building_amount', 'damaged_building_photos',
                'victim_insurance_company', 'victim_insurance_number', 'victim_insurance_type',
                'victim_insurance_issue_date', 'victim_insurance_expiry_date', 'victim_insurance_photo',
                'assessor_name', 'assessor_phone', 'assessor_date',
                'assessor_amount_dinar', 'assessor_amount_dollar', 'assessor_report_photo',
                'admin_number',
            ]);
        });
    }
};
