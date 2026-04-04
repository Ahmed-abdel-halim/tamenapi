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
        Schema::create('monthly_account_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_agent_id')->constrained('branches_agents')->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('due_amount', 15, 2); // القيمة المستحقة
            $table->decimal('paid_amount', 15, 2)->default(0); // المدفوع
            $table->decimal('remaining_amount', 15, 2)->default(0); // المتبقي
            $table->json('documents_data')->nullable(); // بيانات الوثائق
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // منع تكرار نفس الوكيل لنفس الشهر والسنة
            $table->unique(['branch_agent_id', 'year', 'month'], 'unique_agent_month_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_account_closures');
    }
};
