<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->decimal('housing_allowance', 12, 2)->default(0)->after('base_salary');
            $table->decimal('transportation_allowance', 12, 2)->default(0)->after('housing_allowance');
            $table->decimal('communication_allowance', 12, 2)->default(0)->after('transportation_allowance');
            $table->decimal('penalty_amount', 12, 2)->default(0)->after('advance_amount');
            $table->decimal('other_additions', 12, 2)->default(0)->after('penalty_amount');
            $table->string('delivery_method')->default('كاش')->after('status');
            $table->string('custom_delivery_method')->nullable()->after('delivery_method');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'housing_allowance',
                'transportation_allowance',
                'communication_allowance',
                'penalty_amount',
                'other_additions',
                'delivery_method',
                'custom_delivery_method'
            ]);
        });
    }
};
