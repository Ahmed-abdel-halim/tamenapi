<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_custodies', function (Blueprint $table) {
            $table->string('batch_ref', 64)->nullable()->after('serial_end');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_custodies', function (Blueprint $table) {
            $table->dropColumn('batch_ref');
        });
    }
};
