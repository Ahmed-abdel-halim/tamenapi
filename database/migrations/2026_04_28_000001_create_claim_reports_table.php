<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('claim_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->string('report_type');
            $table->string('other_report_type')->nullable();
            $table->date('report_date')->nullable();
            $table->string('preparer_name')->nullable();
            $table->string('report_number')->nullable();
            $table->string('report_image')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('claim_reports');
    }
};
