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
        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('payment_method');
            $table->string('reference_number')->nullable()->after('bank_name'); // رقم الشيك، رقم الحوالة، رقم الواصل
            $table->json('extra_details')->nullable()->after('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_vouchers', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'reference_number', 'extra_details']);
        });
    }
};
