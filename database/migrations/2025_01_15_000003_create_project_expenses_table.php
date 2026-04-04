<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('worker_or_activity_id')->constrained('worker_or_activities')->onDelete('cascade');
            $table->integer('payment_number'); // رقم الدفعة المرتبطة
            $table->enum('expense_type', ['مواد', 'يد عاملة', 'خدمات'])->default('مواد');
            $table->foreignId('main_item_id')->constrained('main_items')->onDelete('cascade');
            $table->string('sub_item'); // البند الفرعي
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->enum('payment_method', ['نقداً', 'بطاقة مصرفية', 'صك مصرفي', 'تحويل مصرفي'])->default('نقداً');
            $table->string('attachment')->nullable(); // ملف مرفق
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_expenses');
    }
};

