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
        Schema::create('advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('treasury_id')->constrained('treasuries');
            $table->decimal('amount', 12, 2); // قيمة السلفة كاملة
            $table->decimal('installment_amount', 12, 2); // قيمة القسط الشهري
            $table->date('loan_date'); // تاريخ إنشاء السلفة (اليوم)
            $table->date('expected_repayment_date'); // تاريخ الانتهاء المتوقع بناءً على القسط
            $table->decimal('treasury_balance_before', 12, 2)->default(0);
            $table->decimal('treasury_balance_after', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advances');
    }
};
