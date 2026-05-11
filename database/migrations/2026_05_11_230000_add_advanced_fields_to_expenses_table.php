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
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('currency')->default('LYD')->after('amount');
            $table->string('voucher_number')->nullable()->after('currency');
            $table->string('receipt_image')->nullable()->after('voucher_number');
            $table->string('expense_type')->default('consumable')->after('receipt_image'); // fixed, consumable
            $table->json('items')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['currency', 'voucher_number', 'receipt_image', 'expense_type', 'items']);
        });
    }
};
