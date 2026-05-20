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
        // Drop the old/malformed table completely
        Schema::dropIfExists('treasury_transactions');

        // Recreate the table with all proper fields
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->string('supplier_phone')->nullable();
            $table->string('source')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('voucher_image')->nullable();
            $table->unsignedBigInteger('branch_agent_id')->nullable();
            $table->string('expense_destination')->nullable();
            $table->string('payment_source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
};
