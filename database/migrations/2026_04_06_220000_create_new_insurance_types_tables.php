<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. تأمين حماية طلاب المدارس
        Schema::create('school_student_insurance_documents', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number')->unique();
            $table->foreignId('branch_agent_id')->nullable()->constrained('branches_agents')->cascadeOnDelete();
            $table->string('student_name');
            $table->string('school_name');
            $table->string('grade')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('premium_amount', 15, 2);
            $table->string('status')->default('active'); // active, cancelled, expired, archived
            $table->timestamps();
        });

        // 2. تأمين نقل النقدية
        Schema::create('cash_in_transit_insurance_documents', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number')->unique();
            $table->foreignId('branch_agent_id')->nullable()->constrained('branches_agents')->cascadeOnDelete();
            $table->string('insured_name');
            $table->string('transit_from')->nullable();
            $table->string('transit_to')->nullable();
            $table->decimal('limit_per_transit', 15, 2);
            $table->decimal('annual_turnover', 15, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('premium_amount', 15, 2);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 3. تأمين شحن البضائع
        Schema::create('cargo_insurance_documents', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number')->unique();
            $table->foreignId('branch_agent_id')->nullable()->constrained('branches_agents')->cascadeOnDelete();
            $table->string('insured_name');
            $table->string('cargo_description');
            $table->string('transport_type')->nullable(); // Sea, Air, Land
            $table->string('voyage_from')->nullable();
            $table->string('voyage_to')->nullable();
            $table->decimal('sum_insured', 15, 2);
            $table->decimal('premium_amount', 15, 2);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_student_insurance_documents');
        Schema::dropIfExists('cash_in_transit_insurance_documents');
        Schema::dropIfExists('cargo_insurance_documents');
    }
};
