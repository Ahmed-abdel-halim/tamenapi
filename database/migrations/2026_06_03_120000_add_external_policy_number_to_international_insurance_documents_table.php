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
        Schema::table('international_insurance_documents', function (Blueprint $table) {
            $table->string('external_policy_number')->nullable()->after('document_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('international_insurance_documents', function (Blueprint $table) {
            $table->dropColumn('external_policy_number');
        });
    }
};
