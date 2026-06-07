<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->decimal('compensation_value', 15, 3)->nullable()->after('assessor_report_photo');
            $table->decimal('additional_expenses', 15, 3)->nullable()->after('compensation_value');
            $table->decimal('total_paid', 15, 3)->nullable()->after('additional_expenses');
            $table->string('recipient_name')->nullable()->after('total_paid');
            $table->string('payment_method')->nullable()->after('recipient_name');
            $table->string('document_number')->nullable()->after('payment_method');
            $table->string('currency')->nullable()->after('document_number');
            $table->string('financial_value_image')->nullable()->after('currency');
            $table->string('sub_category')->nullable()->after('financial_value_image');
            $table->string('finance_status')->default('pending')->after('sub_category');
            $table->text('finance_notes')->nullable()->after('finance_status');
            $table->timestamp('finance_approved_at')->nullable()->after('finance_notes');
            $table->foreignId('finance_user_id')->nullable()->constrained('users')->nullOnDelete()->after('finance_approved_at');
        });
    }

    public function down()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['finance_user_id']);
            $table->dropColumn([
                'compensation_value',
                'additional_expenses',
                'total_paid',
                'recipient_name',
                'payment_method',
                'document_number',
                'currency',
                'financial_value_image',
                'sub_category',
                'finance_status',
                'finance_notes',
                'finance_approved_at',
                'finance_user_id'
            ]);
        });
    }
};
