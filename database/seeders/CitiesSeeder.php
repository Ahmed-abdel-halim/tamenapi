<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\City;

class CitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * رقم اللوحة = order (كما في الجدول الرسمي)
     */
    public function run(): void
    {
        $cities = [
            ['name_ar' => 'سبها', 'name_en' => 'Sabha', 'order' => 1],
            ['name_ar' => 'البيضاء', 'name_en' => 'Al Bayda', 'order' => 2],
            ['name_ar' => 'مصراته', 'name_en' => 'Misrata', 'order' => 3],
            ['name_ar' => 'الزاوية', 'name_en' => 'Zawiya', 'order' => 4],
            ['name_ar' => 'طرابلس', 'name_en' => 'Tripoli', 'order' => 5],
            ['name_ar' => 'الخمس', 'name_en' => 'Al Khums', 'order' => 6],
            ['name_ar' => 'سرت', 'name_en' => 'Sirte', 'order' => 7],
            ['name_ar' => 'بنغازي', 'name_en' => 'Benghazi', 'order' => 8],
            ['name_ar' => 'زوارة', 'name_en' => 'Zuwara', 'order' => 9],
            ['name_ar' => 'درنة', 'name_en' => 'Derna', 'order' => 10],
            ['name_ar' => 'الشاطئ', 'name_en' => 'Ash Shati', 'order' => 11],
            ['name_ar' => 'إجدابيا', 'name_en' => 'Ajdabiya', 'order' => 12],
            ['name_ar' => 'الجفرة', 'name_en' => 'Al Jufra', 'order' => 13],
            ['name_ar' => 'طبرق', 'name_en' => 'Tobruk', 'order' => 14],
            ['name_ar' => 'مرزق', 'name_en' => 'Murzuq', 'order' => 15],
            ['name_ar' => 'الواحات', 'name_en' => 'Al Wahat', 'order' => 16],
            ['name_ar' => 'غدامس', 'name_en' => 'Ghadames', 'order' => 17],
            ['name_ar' => 'المرج', 'name_en' => 'Al Marj', 'order' => 18],
            ['name_ar' => 'نالوت', 'name_en' => 'Nalut', 'order' => 19],
            ['name_ar' => 'الكفرة', 'name_en' => 'Al Kufra', 'order' => 20],
            ['name_ar' => 'غريان', 'name_en' => 'Gharyan', 'order' => 21],
            ['name_ar' => 'يفرن', 'name_en' => 'Yefren', 'order' => 22],
            ['name_ar' => 'أوباري', 'name_en' => 'Awbari', 'order' => 24],
            ['name_ar' => 'سهل جفارة', 'name_en' => 'Sahl Jafara', 'order' => 25],
            ['name_ar' => 'صبراتة', 'name_en' => 'Sabratha', 'order' => 26],
            ['name_ar' => 'ترهونة', 'name_en' => 'Tarhuna', 'order' => 27],
            ['name_ar' => 'بني وليد', 'name_en' => 'Bani Walid', 'order' => 28],
            ['name_ar' => 'الأبيار', 'name_en' => 'Al Abyar', 'order' => 30],
            ['name_ar' => 'غات', 'name_en' => 'Ghat', 'order' => 32],
            ['name_ar' => 'قصر بن غشير', 'name_en' => 'Qasr Bin Ghashir', 'order' => 33],
            ['name_ar' => 'جنزور', 'name_en' => 'Janzour', 'order' => 34],
            ['name_ar' => 'شحات', 'name_en' => 'Shahat', 'order' => 35],
            ['name_ar' => 'زليتن', 'name_en' => 'Zliten', 'order' => 36],
            ['name_ar' => 'باطن الجبل', 'name_en' => 'Batn Al Jabal', 'order' => 37],
            ['name_ar' => 'مزدة', 'name_en' => 'Mizda', 'order' => 38],
            ['name_ar' => 'الجميل', 'name_en' => 'Al Jameel', 'order' => 40],
            ['name_ar' => 'العجيلات', 'name_en' => 'Al Ajaylat', 'order' => 41],
            ['name_ar' => 'الزنتان', 'name_en' => 'Al Zintan', 'order' => 42],
            ['name_ar' => 'جادو', 'name_en' => 'Jadu', 'order' => 43],
            ['name_ar' => 'مسلاتة', 'name_en' => 'Msallata', 'order' => 44],
            ['name_ar' => 'الساحل بالجبل', 'name_en' => 'As Sahil Bil Jabal', 'order' => 45],
            ['name_ar' => 'القربوللي', 'name_en' => 'Al Qurbuli', 'order' => 46],
            ['name_ar' => 'صرمان', 'name_en' => 'Sorman', 'order' => 47],
            ['name_ar' => 'كاباو', 'name_en' => 'Kabao', 'order' => 48],
            ['name_ar' => 'القبة', 'name_en' => 'Al Qubah', 'order' => 49],
            ['name_ar' => 'الأصابعة', 'name_en' => 'Al Asabi\'a', 'order' => 50],
            ['name_ar' => 'رأس لانوف', 'name_en' => 'Ras Lanuf', 'order' => 51],
            ['name_ar' => 'توكرة', 'name_en' => 'Tokra', 'order' => 52],
            ['name_ar' => 'كلة', 'name_en' => 'Kikla', 'order' => 53],
            ['name_ar' => 'الرحيبات', 'name_en' => 'Al Rhybat', 'order' => 54],
            ['name_ar' => 'الشاطئ الغربي', 'name_en' => 'Western Shati', 'order' => 55],
            ['name_ar' => 'الرجبان', 'name_en' => 'Ar Rajban', 'order' => 56],
            ['name_ar' => 'الحرابة', 'name_en' => 'Al Haraba', 'order' => 57],
            ['name_ar' => 'جنوب المرج', 'name_en' => 'South Al Marj', 'order' => 58],
            ['name_ar' => 'الشقيقة', 'name_en' => 'Al Shiqiqah', 'order' => 59],
            ['name_ar' => 'الهلال', 'name_en' => 'Al Hilal', 'order' => 60],
            ['name_ar' => 'زلطن', 'name_en' => 'Zaltan', 'order' => 61],
            ['name_ar' => 'القطرون', 'name_en' => 'Al Qatron', 'order' => 62],
            ['name_ar' => 'الرياينة', 'name_en' => 'Ar Rayayina', 'order' => 65],
            ['name_ar' => 'سلوق وقمينس', 'name_en' => 'Sulug & Qaminis', 'order' => 66],
            ['name_ar' => 'أوجلة وجخرة', 'name_en' => 'Awjila & Jakhira', 'order' => 67],
            ['name_ar' => 'تراغن', 'name_en' => 'Tragin', 'order' => 71],
        ];

        foreach ($cities as $city) {
            City::updateOrCreate(
                ['name_ar' => $city['name_ar']],
                ['name_en' => $city['name_en'], 'order' => $city['order']]
            );
        }
    }
}
