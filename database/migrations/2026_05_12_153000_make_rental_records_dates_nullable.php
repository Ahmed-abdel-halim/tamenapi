<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_records', function (Blueprint $table) {
            $table->date('from_date')->nullable()->change();
            $table->date('to_date')->nullable()->change();
            $table->string('recipient_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rental_records', function (Blueprint $table) {
            $table->date('from_date')->nullable(false)->change();
            $table->date('to_date')->nullable(false)->change();
            $table->string('recipient_name')->nullable(false)->change();
        });
    }
};
