<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plate;
use App\Models\City;

class PlatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // الحصول على المدن مرتبة حسب order
        $cities = City::orderBy('order', 'asc')->get();

        foreach ($cities as $city) {
            // إنشاء لوحة افتراضية لكل مدينة برقم المدينة
            Plate::firstOrCreate(
                [
                    'plate_number' => (string)$city->order,
                    'city_id' => $city->id,
                ],
                [
                    'plate_number' => (string)$city->order,
                    'city_id' => $city->id,
                ]
            );
        }
    }
}
