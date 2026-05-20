<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_transactions', 'payer_phone')) {
                $table->string('payer_phone')->nullable()->after('payer_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('bank_transactions', 'payer_phone')) {
                $table->dropColumn('payer_phone');
            }
        });
    }
};
