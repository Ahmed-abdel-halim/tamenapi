<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modify branches_agents table
        Schema::table('branches_agents', function (Blueprint $table) {
            $table->integer('points_balance')->default(0)->after('status');
            $table->decimal('wallet_balance', 15, 2)->default(0.00)->after('points_balance');
            $table->string('referral_code')->nullable()->unique()->after('wallet_balance');
            $table->unsignedBigInteger('referred_by_id')->nullable()->after('referral_code');
            
            $table->foreign('referred_by_id')->references('id')->on('branches_agents')->onDelete('set null');
        });

        // 2. Create agent_wallet_transactions table
        Schema::create('agent_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_agent_id');
            $table->string('transaction_type'); // 'points', 'cash'
            $table->decimal('amount', 15, 2); 
            $table->string('action'); // 'earn_points', 'redeem_points', 'withdraw_request', 'referral_bonus', 'admin_adjustment'
            $table->text('description');
            $table->timestamps();

            $table->foreign('branch_agent_id')->references('id')->on('branches_agents')->onDelete('cascade');
        });

        // 3. Create agent_withdrawals table
        Schema::create('agent_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_agent_id');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->string('payment_method');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->foreign('branch_agent_id')->references('id')->on('branches_agents')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_withdrawals');
        Schema::dropIfExists('agent_wallet_transactions');
        Schema::table('branches_agents', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropColumn(['points_balance', 'wallet_balance', 'referral_code', 'referred_by_id']);
        });
    }
};
