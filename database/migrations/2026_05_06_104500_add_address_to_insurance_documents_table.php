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
        Schema::table('insurance_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_documents', 'address')) {
                $table->string('address')->nullable()->after('nationality');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_documents', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
