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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('password');
            }
            if (!Schema::hasColumn('users', 'social_security_percentage')) {
                $table->decimal('social_security_percentage', 5, 3)->default(19.475)->after('salary')->comment('حصة الضمان الاجتماعي من المرتب');
            }
            if (!Schema::hasColumn('users', 'tax_percentage')) {
                $table->decimal('tax_percentage', 5, 3)->default(10.000)->after('social_security_percentage')->comment('حصة الضرائب من المرتب');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'social_security_percentage',
                'tax_percentage'
            ]);
        });
    }
};
