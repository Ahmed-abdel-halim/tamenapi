<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MainItem;

class MainItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'التأسيس', 'description' => 'أعمال التأسيس للمشروع'],
            ['name' => 'الهيكل', 'description' => 'أعمال الهيكل الإنشائي'],
            ['name' => 'اللياسة', 'description' => 'أعمال اللياسة والطلاء الخارجي'],
            ['name' => 'السباكة', 'description' => 'أعمال السباكة والتمديدات الصحية'],
            ['name' => 'الكهرباء', 'description' => 'أعمال الكهرباء والتمديدات الكهربائية'],
            ['name' => 'الأرضيات', 'description' => 'أعمال الأرضيات والبلاط'],
            ['name' => 'النوافذ والأبواب', 'description' => 'تركيب النوافذ والأبواب'],
            ['name' => 'الجبس والديكورات', 'description' => 'أعمال الجبس والديكورات الداخلية'],
            ['name' => 'الدهان', 'description' => 'أعمال الدهان والتشطيبات النهائية'],
            ['name' => 'الأعمال الخارجية', 'description' => 'الأعمال الخارجية للمشروع'],
            ['name' => 'الأعمال العامة', 'description' => 'الأعمال العامة والمتنوعة'],
            ['name' => 'التجهيز والمعدات', 'description' => 'تجهيزات المشروع والمعدات'],
        ];

        foreach ($items as $item) {
            MainItem::firstOrCreate(
                ['name' => $item['name']],
                ['description' => $item['description']]
            );
        }
    }
}
