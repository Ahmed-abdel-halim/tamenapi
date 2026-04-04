<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['مقاولات', 'تشطيب'])->default('مقاولات');
            $table->decimal('finishing_percentage', 5, 2)->nullable(); // نسبة التشطيب
            $table->enum('percentage_deduction_type', ['من كل دفعة', 'من إجمالي الدفعات'])->nullable(); // نوع خصم النسبة
            $table->string('name');
            $table->foreignId('owner_id')->constrained('owners')->onDelete('cascade');
            $table->foreignId('supervisor_id')->constrained('supervisors')->onDelete('cascade');
            $table->date('start_date');
            $table->text('agreement_mechanism')->nullable(); // آلية الاتفاق
            $table->decimal('execution_value', 15, 2); // قيمة التنفيذ
            $table->decimal('price_per_meter', 15, 2)->nullable(); // سعر المتر
            $table->enum('classification', ['تجاري', 'سكني', 'تعليمي', 'مصنعي', 'أخرى'])->default('سكني');
            $table->enum('execution_level', ['عادي', 'متوسط', 'عالي'])->default('عادي');
            $table->decimal('project_value', 15, 2); // قيمة المشروع
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

