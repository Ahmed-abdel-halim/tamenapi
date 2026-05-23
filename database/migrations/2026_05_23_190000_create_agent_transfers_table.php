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
        Schema::create('agent_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_agent_id');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method'); // bank_deposit, mobile_payment, bank_cheque, bank_transfer, cash_office, cash_representative, pos_machine
            $table->date('transfer_date');
            $table->string('reference_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('source_bank')->nullable();
            $table->string('source_account_number')->nullable();
            $table->unsignedBigInteger('pos_machine_id')->nullable();
            $table->string('voucher_image')->nullable();
            $table->string('representative_name')->nullable();
            $table->string('exchange_office')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // user_id
            $table->unsignedBigInteger('approved_by')->nullable(); // user_id
            $table->timestamp('approval_date')->nullable();
            $table->unsignedBigInteger('payment_voucher_id')->nullable();
            $table->unsignedBigInteger('treasury_transaction_id')->nullable();
            $table->unsignedBigInteger('bank_transaction_id')->nullable();
            $table->unsignedBigInteger('pos_transaction_id')->nullable();
            $table->timestamps();

            $table->foreign('branch_agent_id')->references('id')->on('branches_agents')->onDelete('cascade');
            $table->foreign('pos_machine_id')->references('id')->on('pos_machines')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_transfers');
    }
};
