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
        Schema::create('external_entities', function (Blueprint $col) {
            $col->id();
            $col->string('name'); // اسم الجهة
            $col->string('entity_number')->nullable(); // رقم الشركة
            $col->string('address')->nullable(); // العنوان
            $col->string('phone')->nullable(); // هاتف الشركة
            $col->string('email')->nullable(); // بريد الشركة
            $col->string('default_messenger_name')->nullable(); // اسم المندوب الافتراضي
            $col->string('default_messenger_phone')->nullable(); // رقم هاتف المندوب
            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_entities');
    }
};
