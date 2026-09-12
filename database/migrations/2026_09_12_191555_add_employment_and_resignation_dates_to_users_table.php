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
            if (!Schema::hasColumn('users', 'hire_date')) {
                $table->date('hire_date')->nullable()->after('account_number')->comment('تاريخ التعيين');
            }
            if (!Schema::hasColumn('users', 'work_start_date')) {
                $table->date('work_start_date')->nullable()->after('hire_date')->comment('بداية العمل (يبدأ منه المرتب)');
            }
            if (!Schema::hasColumn('users', 'resignation_date')) {
                $table->date('resignation_date')->nullable()->after('end_date')->comment('تاريخ الاستقالة (يتوقف بعده المرتب)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('users', 'hire_date')) $cols[] = 'hire_date';
            if (Schema::hasColumn('users', 'work_start_date')) $cols[] = 'work_start_date';
            if (Schema::hasColumn('users', 'resignation_date')) $cols[] = 'resignation_date';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
