<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('colors')) {
            Schema::create('colors', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });

            DB::table('colors')->insert([
                ['name' => 'أبيض - White'],
                ['name' => 'أسود - Black'],
                ['name' => 'رمادي - Gray'],
                ['name' => 'فضي - Silver'],
                ['name' => 'أحمر - Red'],
                ['name' => 'أزرق - Blue'],
                ['name' => 'أخضر - Green'],
                ['name' => 'أصفر - Yellow'],
                ['name' => 'برتقالي - Orange'],
                ['name' => 'بني - Brown'],
                ['name' => 'بيج - Beige'],
                ['name' => 'ذهبي - Gold'],
                ['name' => 'زهري - Pink'],
                ['name' => 'بنفسجي - Purple'],
                ['name' => 'تركوازي - Turquoise'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
