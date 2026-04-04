<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Treasury;

class TreasurySeeder extends Seeder
{
    public function run(): void
    {
        Treasury::firstOrCreate(
            ['name' => 'خزينة العهد'],
            [
                'opening_balance' => 0,
                'current_balance' => 0,
                'notes' => 'خزينة العهد الافتراضية',
                'is_default' => true,
            ]
        );

        Treasury::firstOrCreate(
            ['name' => 'خزينة الأرباح'],
            [
                'opening_balance' => 0,
                'current_balance' => 0,
                'notes' => 'خزينة الأرباح الافتراضية',
                'is_default' => true,
            ]
        );
    }
}
