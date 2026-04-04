<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('ar_SA');

        // إنشاء 1000 تصنيف مصروفات للاختبار
        $categories = [];
        for ($i = 1; $i <= 300; $i++) {
            $categories[] = [
                'name' => 'تصنيف مصروف ' . $i,
                'description' => $faker->sentence(6),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // إدخال على دفعات لتقليل الضغط
        foreach (array_chunk($categories, 200) as $chunk) {
            ExpenseCategory::insert($chunk);
        }
    }
}


