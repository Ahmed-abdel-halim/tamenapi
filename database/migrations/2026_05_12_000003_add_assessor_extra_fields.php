<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('assessor_percentage')->nullable()->after('assessor_amount_dollar');
            $table->string('assessor_other_amount')->nullable()->after('assessor_percentage');
        });
    }

    public function down()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['assessor_percentage', 'assessor_other_amount']);
        });
    }
};
