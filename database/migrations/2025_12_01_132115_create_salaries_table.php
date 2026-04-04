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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('month_name'); // مثل: يونيو
            $table->unsignedTinyInteger('month_number'); // 1-12
            $table->unsignedSmallInteger('year');
            $table->decimal('base_salary', 12, 2);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_rate', 12, 2)->default(0); // أجر الساعة
            $table->decimal('overtime_total', 12, 2)->default(0);
            $table->decimal('total_salary', 12, 2); // الصافي = الأساسي + الإضافي
            $table->foreignId('treasury_id')->constrained('treasuries');
            $table->decimal('treasury_balance_before', 12, 2)->default(0);
            $table->decimal('treasury_balance_after', 12, 2)->default(0);
            $table->date('payment_date'); // تاريخ اليوم
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
