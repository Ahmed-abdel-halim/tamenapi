<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Treasury;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('ar_SA');

        // تأكد من وجود خزائن
        $treasuryIds = Treasury::pluck('id')->all();
        if (empty($treasuryIds)) {
            // لو لا توجد خزائن لا يمكن إنشاء مصاريف
            return;
        }

        // تأكد من وجود تصنيفات
        $categoryIds = ExpenseCategory::pluck('id')->all();
        if (empty($categoryIds)) {
            return;
        }

        $expenses = [];
        for ($i = 1; $i <= 300; $i++) {
            $expenses[] = [
                'name' => 'مصروف تجريبي ' . $i,
                'expense_category_id' => $faker->randomElement($categoryIds),
                'treasury_id' => $faker->randomElement($treasuryIds),
                'amount' => $faker->randomFloat(2, 10, 2000),
                'expense_date' => $faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                'notes' => $faker->boolean(40) ? $faker->sentence(8) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($expenses, 200) as $chunk) {
            Expense::insert($chunk);
        }
    }
}


