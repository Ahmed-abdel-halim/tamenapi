<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_agent_id')->constrained('branches_agents')->onDelete('cascade');
            $table->string('document_type');
            $table->string('document_number');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->string('bank_name');
            $table->string('account_number')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['deposit', 'withdrawal'])->default('deposit');
            $table->boolean('reconciled')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_archives', function (Blueprint $table) {
            $table->id();
            $table->string('document_name');
            $table->string('category');
            $table->string('file_path');
            $table->string('file_type');
            $table->string('file_size');
            $table->string('uploaded_by')->nullable();
            $table->string('related_entity')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('financial_archives');
    }
};
