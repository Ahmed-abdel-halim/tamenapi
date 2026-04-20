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
        Schema::create('union_balance_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->nullable();
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('card_price', 15, 2);
            $table->decimal('union_fee_per_card', 15, 2);
            $table->decimal('company_deposit_per_card', 15, 2);
            $table->integer('cards_count');
            $table->decimal('total_union_fee', 15, 2);
            $table->decimal('total_company_deposit', 15, 2);
            $table->string('payment_method')->nullable();
            $table->date('purchase_date');
            $table->string('receipt_image')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('union_balance_purchases');
    }
};
