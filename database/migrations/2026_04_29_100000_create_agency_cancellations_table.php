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
        Schema::create('agency_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_agent_id')->constrained('branches_agents')->onDelete('cascade');
            $table->text('reason');
            $table->date('cancellation_date');
            $table->text('custody_handover_details')->nullable();
            $table->string('manager_signature')->nullable();
            $table->string('finance_signature')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_cancellations');
    }
};
