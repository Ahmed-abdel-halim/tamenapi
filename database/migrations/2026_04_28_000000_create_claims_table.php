<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->string('reference_number')->nullable();
            $table->date('claim_date');
            $table->date('accident_date');
            $table->string('damage_type');
            $table->string('other_damage_type')->nullable();
            
            $table->string('claimant_name');
            $table->string('kinship');
            $table->string('personal_id');
            $table->string('nationality');
            $table->string('phone_number');
            
            $table->string('document_coverage')->nullable(); // Comprehensive, Third Party, etc.
            
            // Polymorphic relation to document
            $table->nullableMorphs('document');
            
            $table->string('status')->default('pending'); // current status
            $table->foreignId('branch_agent_id')->nullable(); // For access control
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('claims');
    }
};
