<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        // Insert default categories
        $defaultCategories = [
            'قرطاسية', 'صيانة', 'خدمات', 'إيجار', 'ضيافة', 'التعويضات',
            'قرطاسيه مكتبيه مستهلكه',
            'مصاريف (رصيد واشتراكات حكوميه (كهرباء -انترنت -رصيد اتصالات-ماء -صرف صحي )',
            'مصاريف مواد تنظيف',
            'عهده ماليه خاصه بالموظفين',
            'صيانة (الكترونيات - المبنى - الاثاث -الخ )',
            'صيانه السيارات الخاصه بالموظفين والخدمات',
            'الكترونيات ثابته',
            'دعايه واعلان وهدايا مستهلكه (( خاص بالوكلاء ))',
            'رسوم ومصاريف اشتراكات المعارض والاجتماعات الخاصه بالشركه',
            'رسوم اصدار وتجديد غرفه التجاره والصناعه والزراعه',
            'قرطاسيه مكتبيه ثابته',
            'رسوم اشتراكات اعاده التامين',
            'مصلحة الضرائب والميزانيات'
        ];

        foreach ($defaultCategories as $category) {
            DB::table('expense_categories')->updateOrInsert(
                ['name' => $category],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down()
    {
        Schema::dropIfExists('expense_categories');
    }
};
