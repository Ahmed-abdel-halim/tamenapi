<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up() {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('damaged_vehicle_type')->nullable()->after('damaged_vehicle_model');
            $table->string('damaged_vehicle_color')->nullable()->after('damaged_vehicle_type');
        });
    }
    public function down() {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['damaged_vehicle_type', 'damaged_vehicle_color']);
        });
    }
};
