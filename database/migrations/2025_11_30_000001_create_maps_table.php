<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('owners')->onDelete('set null');
            $table->string('owner_name')->nullable(); // للشخص الآخر غير المالك
            $table->string('phone')->nullable();
            $table->decimal('area', 15, 2); // مقاس الخريطة بالمتر المربع
            $table->decimal('price_per_meter', 15, 2); // سعر المتر
            $table->decimal('survey_lift_price', 15, 2)->nullable(); // سعر الرفع المساحي
            $table->decimal('koroki_drawing_price', 15, 2)->nullable(); // سعر الرسم الكروركي
            $table->decimal('building_lift_price', 15, 2)->nullable(); // سعر رفع المبنى
            $table->decimal('interior_design_price', 15, 2)->nullable(); // سعر التصميم الداخلي
            $table->decimal('total_price', 15, 2); // الإجمالي
            $table->decimal('paid_amount', 15, 2)->default(0); // المبلغ المدفوع
            $table->enum('payment_status', ['مدفوع', 'دفع_جزئي', 'مستحق'])->default('مستحق');
            $table->enum('payment_method', ['نقداً', 'بطاقة_مصرفية', 'صك_مصرفي', 'تحويل_مصرفي'])->nullable();
            $table->foreignId('engineer_id')->nullable()->constrained('engineers')->onDelete('set null');
            $table->decimal('engineer_percentage', 5, 2)->nullable(); // النسبة للمهندس
            $table->decimal('engineer_amount', 15, 2)->nullable(); // المبلغ المحول للمهندس
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maps');
    }
};

