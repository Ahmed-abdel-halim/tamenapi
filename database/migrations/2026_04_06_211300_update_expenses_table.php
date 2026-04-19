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
            if (!Schema::hasColumn('expenses', 'category')) {
                $table->string('category')->nullable()->after('name');
            }
            if (!Schema::hasColumn('expenses', 'status')) {
                $table->string('status')->default('مدفوع')->after('expense_date');
            }
            // Make category FK nullable for simple usage
            if (Schema::hasColumn('expenses', 'expense_category_id')) {
                $table->unsignedBigInteger('expense_category_id')->nullable()->change();
            }
            if (Schema::hasColumn('expenses', 'treasury_id')) {
                $table->unsignedBigInteger('treasury_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['category', 'status']);
        });
    }
};
