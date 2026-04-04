<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->integer('payment_number'); // رقم الدفعة
            $table->date('payment_date');
            $table->enum('payment_method', ['نقداً', 'بطاقة مصرفية', 'صك مصرفي', 'تحويل مصرفي'])->default('نقداً');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_payments');
    }
};

