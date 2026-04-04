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
        Schema::create('insurance_ownership_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_document_id')->constrained('insurance_documents')->onDelete('cascade');
            
            // البيانات السابقة
            $table->foreignId('previous_plate_id')->nullable()->constrained('plates')->onDelete('set null');
            $table->string('previous_plate_number_manual')->nullable();
            $table->string('previous_insured_name')->nullable();
            $table->string('previous_phone')->nullable();
            $table->string('previous_driving_license_number')->nullable();
            
            // البيانات الجديدة
            $table->foreignId('new_plate_id')->nullable()->constrained('plates')->onDelete('set null');
            $table->string('new_plate_number_manual')->nullable();
            $table->string('new_insured_name');
            $table->string('new_phone')->nullable();
            $table->string('new_driving_license_number')->nullable();
            
            $table->timestamp('transferred_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_ownership_transfers');
    }
};
