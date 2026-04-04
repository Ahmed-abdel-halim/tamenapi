<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);
        $this->call(TreasurySeeder::class);
        $this->call(ExpenseCategorySeeder::class);
        $this->call(ExpenseSeeder::class);
        $this->call(CitiesSeeder::class);
        $this->call(PlatesSeeder::class);
    }
}
