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
        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('is_indemnity')->default(false)->after('notes');
            $table->string('indemnity_type')->nullable()->after('is_indemnity');
            $table->string('payment_source')->default('bank')->after('indemnity_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['is_indemnity', 'indemnity_type', 'payment_source']);
        });
    }
};
