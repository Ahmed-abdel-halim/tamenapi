<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('union_balance_purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('union_balance_purchases', 'supervision_fee_per_card')) {
                $table->decimal('supervision_fee_per_card', 15, 2)->default(0)->after('union_fee_per_card');
            }
            if (!Schema::hasColumn('union_balance_purchases', 'total_supervision_fee')) {
                $table->decimal('total_supervision_fee', 15, 2)->default(0)->after('total_union_fee');
            }
            if (!Schema::hasColumn('union_balance_purchases', 'bank_deposit_image')) {
                $table->string('bank_deposit_image')->nullable()->after('receipt_image');
            }
            if (!Schema::hasColumn('union_balance_purchases', 'depositor_name')) {
                $table->string('depositor_name')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('union_balance_purchases', 'deposit_date')) {
                $table->date('deposit_date')->nullable()->after('depositor_name');
            }
            if (!Schema::hasColumn('union_balance_purchases', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('deposit_date');
            }
            if (!Schema::hasColumn('union_balance_purchases', 'deposit_amount')) {
                $table->decimal('deposit_amount', 15, 2)->nullable()->after('bank_account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('union_balance_purchases', function (Blueprint $table) {
            $cols = ['supervision_fee_per_card', 'total_supervision_fee', 'bank_deposit_image', 'depositor_name', 'deposit_date', 'bank_account_number', 'deposit_amount'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('union_balance_purchases', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};