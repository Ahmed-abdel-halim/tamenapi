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
        Schema::table('branches_agents', function (Blueprint $table) {
            $table->string('office_facade_photo')->nullable();
            $table->string('office_phone')->nullable();
            $table->text('office_location')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches_agents', function (Blueprint $table) {
            $table->dropColumn(['office_facade_photo', 'office_phone', 'office_location']);
        });
    }
};
