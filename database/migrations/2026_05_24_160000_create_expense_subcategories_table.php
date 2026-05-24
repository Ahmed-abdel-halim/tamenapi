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
        Schema::create('expense_subcategories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name'); // e.g. 'مصاريف تشغيلية'
            $table->string('name');          // e.g. 'الرواتب'
            $table->timestamps();
        });

        // Preseed the default subcategories
        $defaults = [
            // المصروفات التشغيلية
            ['category_name' => 'مصاريف تشغيلية', 'name' => 'الرواتب', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف تشغيلية', 'name' => 'الإيجارات', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف تشغيلية', 'name' => 'الكهرباء', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف تشغيلية', 'name' => 'المياه', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف تشغيلية', 'name' => 'الإنترنت والاتصالات', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف تشغيلية', 'name' => 'الوقود', 'created_at' => now(), 'updated_at' => now()],

            // المصروفات الفنية
            ['category_name' => 'مصاريف فنية', 'name' => 'التعويضات المدفوعة', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف فنية', 'name' => 'عمولات الوسطاء', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف فنية', 'name' => 'مصروفات المعاينة', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف فنية', 'name' => 'مصروفات الخبراء', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف فنية', 'name' => 'إعادة التأمين', 'created_at' => now(), 'updated_at' => now()],

            // المصروفات الإدارية
            ['category_name' => 'مصاريف إدارية', 'name' => 'صيانة', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف إدارية', 'name' => 'قرطاسية', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف إدارية', 'name' => 'ضيافة', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف إدارية', 'name' => 'دعاية وإعلان', 'created_at' => now(), 'updated_at' => now()],
            ['category_name' => 'مصاريف إدارية', 'name' => 'تدريب الموظفين', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('expense_subcategories')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_subcategories');
    }
};
