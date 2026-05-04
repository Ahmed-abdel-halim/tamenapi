<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('owner_name');
            $table->string('phone');
            $table->string('national_id');
            $table->string('personal_photo')->nullable();
            $table->string('id_photo')->nullable();
            $table->string('national_id_photo')->nullable();
            $table->json('contract_photos')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_voucher_id')->constrained('rental_vouchers')->onDelete('cascade');
            $table->date('from_date');
            $table->date('to_date');
            $table->integer('apartments_count')->default(1);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('recipient_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_records');
        Schema::dropIfExists('rental_vouchers');
    }
};
