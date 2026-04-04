<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_id')->constrained('treasuries')->onDelete('cascade');
            $table->enum('type', ['إضافة', 'سحب', 'تحويل_خارج', 'تحويل_داخل', 'تحديث_رصيد_افتتاحي']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('related_type')->nullable(); // project_payment, project_expense, transfer, opening_balance_update
            $table->unsignedBigInteger('related_id')->nullable();
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->foreignId('to_treasury_id')->nullable()->constrained('treasuries')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
};

