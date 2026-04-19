<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE store_items MODIFY category VARCHAR(100) NOT NULL DEFAULT 'paper'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE store_items MODIFY category ENUM('paper','electronic','furniture','other') NOT NULL DEFAULT 'paper'");
    }
};
