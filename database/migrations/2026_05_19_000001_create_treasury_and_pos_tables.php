<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. توسعة جدول bank_transactions (إضافة الحقول الجديدة فقط إذا لم تكن موجودة)
        Schema::table('bank_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_transactions', 'transaction_type')) {
                $table->string('transaction_type')->default('bank_transfer')->after('type');
            }
            if (!Schema::hasColumn('bank_transactions', 'source_bank')) {
                $table->string('source_bank')->nullable()->after('transaction_type');
            }
            if (!Schema::hasColumn('bank_transactions', 'destination_bank')) {
                $table->string('destination_bank')->nullable()->after('source_bank');
            }
            if (!Schema::hasColumn('bank_transactions', 'agent_name')) {
                $table->string('agent_name')->nullable()->after('destination_bank');
            }
            if (!Schema::hasColumn('bank_transactions', 'branch_agent_id')) {
                $table->unsignedBigInteger('branch_agent_id')->nullable()->after('agent_name');
            }
            if (!Schema::hasColumn('bank_transactions', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('branch_agent_id');
            }
            if (!Schema::hasColumn('bank_transactions', 'voucher_image')) {
                $table->string('voucher_image')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('bank_transactions', 'payer_name')) {
                $table->string('payer_name')->nullable()->after('voucher_image');
            }
            if (!Schema::hasColumn('bank_transactions', 'payer_phone')) {
                $table->string('payer_phone')->nullable()->after('payer_name');
            }
        });

        // 2. جدول معاملات الخزنة
        if (!Schema::hasTable('treasury_transactions')) {
            Schema::create('treasury_transactions', function (Blueprint $table) {
                $table->id();
                $table->date('transaction_date');
                $table->enum('type', ['income', 'expense']);
                $table->decimal('amount', 15, 2);
                $table->string('description');
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

        // 3. جدول ماكينات POS
        if (!Schema::hasTable('pos_machines')) {
            Schema::create('pos_machines', function (Blueprint $table) {
                $table->id();
                $table->string('machine_name');
                $table->string('machine_serial')->nullable();
                $table->string('bank_name');
                $table->string('merchant_id')->nullable();
                $table->string('location')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 4. جدول معاملات POS
        if (!Schema::hasTable('pos_transactions')) {
            Schema::create('pos_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pos_machine_id');
                $table->date('transaction_date');
                $table->decimal('amount', 15, 2);
                $table->integer('transactions_count')->default(1);
                $table->string('reference_number')->nullable();
                $table->string('report_file')->nullable();
                $table->boolean('is_reconciled')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->foreign('pos_machine_id')->references('id')->on('pos_machines')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
        Schema::dropIfExists('pos_machines');
        Schema::dropIfExists('treasury_transactions');

        Schema::table('bank_transactions', function (Blueprint $table) {
            $cols = ['transaction_type','source_bank','destination_bank',
                     'agent_name','branch_agent_id','payment_method',
                     'voucher_image','payer_name','payer_phone'];
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('bank_transactions', $c));
            if ($existing) $table->dropColumn(array_values($existing));
        });
    }
};
