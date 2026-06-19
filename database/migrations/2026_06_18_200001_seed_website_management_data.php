<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ─── إعدادات الموقع ──────────────────────────────────────────────────
        $settings = [
            ['key' => 'phone', 'value' => '+218 920003366'],
            ['key' => 'email', 'value' => 'info@mli.ly'],
            ['key' => 'whatsapp', 'value' => '218920003366'],
            ['key' => 'facebook_url', 'value' => 'https://facebook.com'],
            ['key' => 'twitter_url', 'value' => 'https://x.com'],
            ['key' => 'linkedin_url', 'value' => ''],
            ['key' => 'youtube_url', 'value' => 'https://youtube.com'],
            ['key' => 'instagram_url', 'value' => ''],
            ['key' => 'address_ar', 'value' => 'طرابلس، ليبيا'],
            ['key' => 'address_en', 'value' => 'Tripoli, Libya'],
        ];
        foreach ($settings as $s) {
            DB::table('site_settings')->insert(array_merge($s, ['created_at' => now(), 'updated_at' => now()]));
        }

        // ─── بنرات الصفحة الرئيسية ──────────────────────────────────────────
        $sliders = [
            [
                'media_type' => 'image',
                'media_url' => '/new/قبل الفوتر1.png',
                'title_ar' => 'المدار الليبي للتأمين',
                'title_en' => 'Al Madar Libyan Insurance',
                'subtitle_ar' => 'شركة تأمين رائدة في ليبيا، نقدم حلولاً تأمينية شاملة ومبتكرة لحماية مستقبلك وممتلكاتك',
                'subtitle_en' => 'A leading insurance company in Libya, providing comprehensive and innovative solutions to protect your future and assets.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'media_type' => 'image',
                'media_url' => '/new/قبل الفوتر 3.png',
                'title_ar' => 'المدار الليبي للتأمين',
                'title_en' => 'Al Madar Libyan Insurance',
                'subtitle_ar' => 'شركة تأمين رائدة في ليبيا، نقدم حلولاً تأمينية شاملة ومبتكرة لحماية مستقبلك وممتلكاتك',
                'subtitle_en' => 'A leading insurance company in Libya, providing comprehensive and innovative solutions to protect your future and assets.',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];
        foreach ($sliders as $slider) {
            DB::table('homepage_sliders')->insert(array_merge($slider, ['created_at' => now(), 'updated_at' => now()]));
        }

        // ─── خدمات الصفحة الرئيسية (الكاردات الثمانية) ──────────────────────
        $services = [
            [
                'title_ar' => 'تأمين السيارات',
                'title_en' => 'Car Insurance',
                'desc_ar' => 'تأمين شامل وإجباري للسيارات يغطي جميع احتياجاتك من الحماية',
                'desc_en' => 'Comprehensive and mandatory car insurance for full protection',
                'icon' => 'fas fa-car',
                'image_url' => '/new/تامين السيارات .jpg',
                'link' => '/insurances#insurance-1',
                'sort_order' => 1,
            ],
            [
                'title_ar' => 'تأمين القوارب',
                'title_en' => 'Boat Insurance',
                'desc_ar' => 'تأمين شامل للقوارب والمركبات البحرية ضد جميع المخاطر',
                'desc_en' => 'Comprehensive coverage for boats and marine vessels against all risks',
                'icon' => 'fas fa-ship',
                'image_url' => '/new/تامين المراكب.png',
                'link' => '/insurances#insurance-5',
                'sort_order' => 2,
            ],
            [
                'title_ar' => 'الحوادث الشخصية',
                'title_en' => 'Personal Accidents',
                'desc_ar' => 'حماية من الحوادث الشخصية والإصابات مع تعويضات مالية',
                'desc_en' => 'Protection against personal accidents and injuries with financial compensation',
                'icon' => 'fas fa-running',
                'image_url' => '/new/الحوادث الشخصية.png',
                'link' => '/insurances#insurance-6',
                'sort_order' => 3,
            ],
            [
                'title_ar' => 'تأمين طبي',
                'title_en' => 'Medical Insurance',
                'desc_ar' => 'تأمين رعاية صحية شامل لك ولعائلتك في أفضل المصحات',
                'desc_en' => 'Comprehensive health care insurance for you and your family in the best clinics',
                'icon' => 'fas fa-hospital',
                'image_url' => '/new/Local Health Insurance 1.png',
                'link' => '/insurances#insurance-4',
                'sort_order' => 4,
            ],
            [
                'title_ar' => 'تأمين المسافرين',
                'title_en' => 'Travel Insurance',
                'desc_ar' => 'حماية شاملة للمسافرين أثناء السفر مع تغطية طبية ومالية كاملة',
                'desc_en' => 'Full protection for travelers with complete medical and financial coverage',
                'icon' => 'fas fa-plane',
                'image_url' => '/new/تامين المسافرن.png',
                'link' => '/insurances#insurance-2',
                'sort_order' => 5,
            ],
            [
                'title_ar' => 'تأمين زوار ليبيا',
                'title_en' => 'Libya Visitors Insurance',
                'desc_ar' => 'تأمين خاص لزوار ليبيا يغطي احتياجاتهم خلال فترة الإقامة',
                'desc_en' => 'Specialized insurance for visitors to Libya covering their needs during stay',
                'icon' => 'fas fa-map-marked-alt',
                'image_url' => '/new/تامين زوار ليبيا.png',
                'link' => '/insurances#insurance-3',
                'sort_order' => 6,
            ],
            [
                'title_ar' => 'تأمين وافدين للمقيمين',
                'title_en' => 'Resident Insurance for Expats',
                'desc_ar' => 'تأمين خاص للوافدين المقيمين في ليبيا يغطي احتياجاتهم الصحية والمالية',
                'desc_en' => 'Specialized insurance for expatriates residing in Libya covering their health and financial needs',
                'icon' => 'fas fa-user-tie',
                'image_url' => '/new/تامين الوافدين.png',
                'link' => '/insurances#insurance-8',
                'sort_order' => 7,
            ],
            [
                'title_ar' => 'تأمين الحج والعمرة',
                'title_en' => 'Hajj and Umrah Insurance',
                'desc_ar' => 'تأمين خاص للحجاج والمعتمرين يغطي جميع احتياجاتهم خلال الرحلة',
                'desc_en' => 'Special insurance for pilgrims and Umrah performers covering all their needs during the journey',
                'icon' => 'fas fa-kaaba',
                'image_url' => '/new/تامين الحج.png',
                'link' => '/insurances#insurance-7',
                'sort_order' => 8,
            ],
        ];
        foreach ($services as $svc) {
            DB::table('homepage_services')->insert(array_merge($svc, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]));
        }

        // ─── أنواع التأمين (صفحة التأمينات التفصيلية) ────────────────────────
        $types = [
            [
                'title_ar' => 'تأمين السيارات',
                'title_en' => 'Car Insurance',
                'description_ar' => 'إدارة السيارات بشركة المدار الليبي للتأمين تقوم بإصدار مجموعة من وثائق تأمين السيارات',
                'description_en' => 'The Car Insurance Department at Almadar Libya Insurance Company issues a range of car insurance documents',
                'details_ar' => "نفيدكم بأن إدارة السيارات بشركة المدار الليبي للتأمين تقوم بإصدار مجموعة من وثائق تأمين السيارات وهي مبينة على النحو التالي:\n\nوثائق التأمين الإجباري:\nتصدر هذه التغطية وفقا للقانون رقم 28 لسنة 1971 بشأن المسؤولية المدنية التأمينية عن حوادث المركبات الآلية داخل ليبيا عن حالات الوفاة والإصابات البدنية.\n\nالبطاقة العربية الموحدة (البطاقة البرتقالية):\nتغطى هذه الوثيقة الحوادث التي تقع خارج ليبيا وتسرى سريان البلد المزار وتحل المكاتب الإقليمية في البلد المزار محل شركة التأمين المصدر للوثيقة.\n\nوثائق أضرار الطرف الثالث:\nتغطى هذه الوثيقة الأضرار التي تلحق بممتلكات الغير (طرف ثالث) والناتجة عن اصطدام السيارة المؤمنة التي تصيب الغير.\n\nوثيقة تأمين السيارات الأجنبية:\nتصدر هذه التغطية وفقا للقانون رقم 28 لسنة 1971 بشأن المسؤولية المدنية التأمينية عن حوادث المركبات الآلية داخل ليبيا عن حالات الوفاة والإصابات البدنية.",
                'details_en' => "We inform you that the Car Insurance Department at Almadar Libya Insurance Company issues a range of car insurance documents as follows:\n\nMandatory Insurance Documents:\nThis coverage is issued in accordance with Law No. 28 of 1971 regarding civil liability insurance for motor vehicle accidents within Libya for cases of death and physical injuries.\n\nUnified Arab Card (Orange Card):\nThis document covers accidents that occur outside Libya and applies in the visited country, with regional offices in the visited country replacing the issuing insurance company.\n\nThird-Party Damage Documents:\nThis document covers damages to third-party property resulting from collisions involving the insured vehicle.\n\nForeign Car Insurance Document:\nThis coverage is issued in accordance with Law No. 28 of 1971 regarding civil liability insurance for motor vehicle accidents within Libya for cases of death and physical injuries.",
                'icon' => 'fas fa-car',
                'color' => '#667eea',
                'image_url' => '/new/تامين السيارات .jpg',
                'sort_order' => 1,
            ],
            [
                'title_ar' => 'تأمين المسافرين',
                'title_en' => 'Travel Insurance',
                'description_ar' => 'إدارة التأمين الصحي تقوم بإصدار وثائق تأمين المسافرين من خلال إبرامها اتفاقيات مع شركات ذات سمعة طيبة',
                'description_en' => 'The Health Insurance Department issues travel insurance documents through agreements with reputable companies',
                'details_ar' => "أن إدارة التأمين الصحي تقوم بإصدار وثائق تأمين المسافرين من خلال إبرامها اتفاقيات مع شركات ذات سمعة طيبة في هذا المجال وبأسعار منافسة جداً مقارنة بما هو متوفر في السوق الليبي و بأسلوب متطور و حضاري مما جعلها من الشركات المنافسة في هذا المجال.\n\nذلك من خلال وثيقة تأمين المسافرين والتي تغطي الحالات الطارئة و بالميزات التالية:\n• ضمانات متعددة وبأقل الأسعار\n• سهولة الحصول على طلب المساعدة بالخارج\n• تغطية طبية عاجلة وشاملة نتيجة المرض المفاجئ أو الحوادث العرضية\n• إرسال الأدوية وبصورة عاجلة للمرضى\n• تعويض عن ضرر فقدان الأمتعة\n\nفلا تتردد في الحصول على هذه الوثيقة لتتحصل على كافة الضمانات والتسهيلات المذكورة أعلاه.",
                'details_en' => "The Health Insurance Department issues travel insurance documents through agreements with reputable companies in this field at very competitive prices compared to what is available in the Libyan market, with a modern and civilized approach that has made it one of the competing companies in this field.\n\nThrough the travel insurance document which covers emergency cases with the following features:\n• Multiple guarantees at the lowest prices\n• Easy access to assistance requests abroad\n• Urgent and comprehensive medical coverage for sudden illness or accidental injuries\n• Urgent delivery of medicines to patients\n• Compensation for lost luggage damage\n\nDo not hesitate to obtain this document to get all the guarantees and facilities mentioned above.",
                'icon' => 'fas fa-plane',
                'color' => '#f093fb',
                'image_url' => '/new/تامين المسافرن.png',
                'sort_order' => 2,
            ],
            [
                'title_ar' => 'تأمين زوار ليبيا',
                'title_en' => 'Libya Visitors Insurance',
                'description_ar' => 'وثيقة تأمين مصممة خصيصاً لزوار دولة ليبيا من المتطلبات الرسمية في الإدارة العامة للجوازات',
                'description_en' => 'Insurance document specially designed for visitors to Libya, an official requirement of the General Passports Administration',
                'details_ar' => "أهم مزايا تأمين وثيقة زوار ليبيا:\n\nهي واحدة من وثائق التأمين التي صممتها شركتنا خصيصاً لزوار دولة ليبيا، وهي من المتطلبات الرسمية في الإدارة العامة للجوازات لمنح تأشيرات الدخول للأجانب القاصدين ليبيا مثل:\n• تأشيرات الالتحاق بالوالد المقيم\n• تأشيرات مهمة رسمية\n• تأشيرات سياحية\n• تأشيرات زيارة\n\nتوفر وثيقة \"زوار ليبيا\" التغطية التأمينية لزوار دولة ليبيا الذين يحملون تأشيرة دخول صالحة، وتحميهم في الحالات الطبية الطارئة والنفقات التي تترتب عليهم خلال مدة التأشيرة المصرح بها.\n\nتبدأ التغطية التأمينية فور وصول المؤمن له إلى أحد المنافذ البرية أو الجوية أو البحرية للدولة.",
                'details_en' => "Key features of Libya Visitors Insurance:\n\nIt is one of the insurance documents designed by our company specifically for visitors to Libya, and it is an official requirement of the General Passports Administration to grant entry visas to foreigners heading to Libya such as:\n• Resident parent reunion visas\n• Official mission visas\n• Tourist visas\n• Visit visas\n\nThe \"Libya Visitors\" document provides insurance coverage for visitors to Libya who hold valid entry visas, and protects them in emergency medical cases and expenses incurred during the authorized visa period.\n\nInsurance coverage begins immediately upon the insured's arrival at one of the country's land, air, or sea ports.",
                'icon' => 'fas fa-passport',
                'color' => '#4facfe',
                'image_url' => '/new/تامين زوار ليبيا.png',
                'sort_order' => 3,
            ],
            [
                'title_ar' => 'تأمين المسؤولية الطبية',
                'title_en' => 'Medical Liability Insurance',
                'description_ar' => 'تقوم الإدارة بإصدار وثائق تأمين المسئولية الطبية وفق قانون المسئولية الطبية رقم 17 لسنة 1986',
                'description_en' => 'The Department issues medical liability insurance documents in accordance with Medical Liability Law No. 17 of 1986',
                'details_ar' => "تقوم الإدارة بإصدار وثائق تأمين المسئولية الطبية وفق قانون المسئولية الطبية رقم 17 لسنة 1986 والذي يمنح العناصر الطبية والطبية المساعدة الطمأنينة في مزاولة أعمالهم حيث توفير الغطاء التأميني لأي خطأ طبي يصدر عنهم.\n\nومع كل ما سبق تأمل هذه الإدارة أن تكون قد قدمت كل ما بوسعها للرقي بشركة المدار الليبي وكلها أمل في أن تقدم كل ما بوسعها لتقديم الأفضل.\n\nوفي النهاية تمنياتنا للجميع بدوام الصحة والعافية والشفاء العاجل لكل مريض.",
                'details_en' => "The Department issues medical liability insurance documents in accordance with Medical Liability Law No. 17 of 1986, which provides medical and medical assistant personnel with peace of mind in practicing their work by providing insurance coverage for any medical error they may commit.\n\nWith all of the above, this Department hopes that it has done everything in its power to elevate Almadar Libya Company and hopes to do everything in its power to provide the best.\n\nFinally, we wish everyone continued health and wellness and a speedy recovery for every patient.",
                'icon' => 'fas fa-heartbeat',
                'color' => '#fa709a',
                'image_url' => '/new/Local Health Insurance 1.png',
                'sort_order' => 4,
            ],
            [
                'title_ar' => 'تأمين القوارب',
                'title_en' => 'Boat Insurance',
                'description_ar' => 'التأمين على القوارب والدراجات البحرية وقوارب الصيد',
                'description_en' => 'Insurance for boats, jet skis, and fishing boats',
                'details_ar' => "التأمين على القوارب والدراجات البحرية وقوارب الصيد:\n\nوثيقة تكميلي:\nنقدم حلول تأمين موثوقة بتكلفة مدروسة لتغطية اليخوت والمراكب (الترفيهية والصيد) حسب التغطية المطلوبة لكي تبحر دون قلق.\n\nوثيقة إجباري:\nتصدر هذه التغطية وفقا للقانون رقم 28 لسنة 1971 بشأن المسؤولية المدنية التأمينية عن حوادث المركبات الآلية داخل ليبيا عن حالات الوفاة والإصابات البدنية (تغطية خاصة للمراكب البحرية مثل: القوارب الخاصة – الدراجات البحرية – قوارب الصيد).",
                'details_en' => "Insurance for boats, jet skis, and fishing boats:\n\nSupplementary Document:\nWe provide reliable insurance solutions at a calculated cost to cover yachts and vessels (recreational and fishing) according to the required coverage so you can sail without worry.\n\nMandatory Document:\nThis coverage is issued in accordance with Law No. 28 of 1971 regarding civil liability insurance for motor vehicle accidents within Libya for cases of death and physical injuries (special coverage for marine vessels such as: private boats – jet skis – fishing boats).",
                'icon' => 'fas fa-ship',
                'color' => '#43e97b',
                'image_url' => '/new/تامين المراكب.png',
                'sort_order' => 5,
            ],
            [
                'title_ar' => 'الحوادث الشخصية',
                'title_en' => 'Personal Accidents',
                'description_ar' => 'وثيقة تأمين ضد الحوادث الشخصية لحماية مستقبلك من الحوادث المفاجئة',
                'description_en' => 'Personal accident insurance document to protect your future from sudden accidents',
                'details_ar' => "وثيقة تأمين ضد الحوادث الشخصية:\n\nنحمي مستقبلك من الحوادث المفاجئة لكي تستمتع بالحياة دون قلق. وذلك من خلال تأمين الحوادث الشخصية.\n\nقد تجبرك الحوادث غير المتوقعة على تغيير نمط حياتك أو تعطل أعمالك، سواء كانت حوادث بسيطة كالتعثر أثناء المشي، أو أكثر خطورة كالتعرض لحادث مركبة يجبرك على دخول المستشفى.\n\nلذلك صممنا لك وثائق التأمين ضد الحوادث الشخصية لنؤمن لك الحماية طوال الوقت، ونضمن لك الأمان وراحة البال أينما كنت ومهما حدث.\n\nما الذي ستحصل عليه من خلال تأمين الحوادث الشخصية لدينا:\n• دفع مبلغ التأمين الإجمالي إذا تعرضت لحادث سبب لك الإعاقة الدائمة\n• في حالة الحوادث الأقل خطورة التي تسبب إعاقة جزئية أو دائمة، ندفع نسبتك من مبلغ التأمين، وتحسب النسبة على أساس شدة الإصابة ومدى تأثيرها على الجسم\n• نغطي جميع النفقات الطبية إذا احتجت إلى دخول المستشفى أو العلاج الطبي خارج البلاد",
                'details_en' => "Personal Accident Insurance Document:\n\nWe protect your future from sudden accidents so you can enjoy life without worry. This is through personal accident insurance.\n\nUnexpected accidents may force you to change your lifestyle or disrupt your business, whether they are simple accidents like tripping while walking, or more serious like being involved in a vehicle accident that forces you to enter the hospital.\n\nTherefore, we have designed personal accident insurance documents for you to provide you with protection at all times, and ensure your safety and peace of mind wherever you are and whatever happens.\n\nWhat you will get through our personal accident insurance:\n• Payment of the total insurance amount if you are exposed to an accident that causes you permanent disability\n• In the case of less serious accidents that cause partial or permanent disability, we pay your share of the insurance amount, and the percentage is calculated based on the severity of the injury and its impact on the body\n• We cover all medical expenses if you need to enter the hospital or receive medical treatment abroad",
                'icon' => 'fas fa-user-injured',
                'color' => '#fee140',
                'image_url' => '/new/الحوادث الشخصية.png',
                'sort_order' => 6,
            ],
            [
                'title_ar' => 'تأمين الحج والعمرة',
                'title_en' => 'Hajj and Umrah Insurance',
                'description_ar' => 'وثيقة تأمين الحج والعمرة تشمل التغطية للحالات الصحية الطارئة وإصابات كوفيد-19',
                'description_en' => 'Hajj and Umrah insurance document includes coverage for emergency health cases and COVID-19 injuries',
                'details_ar' => "وثيقة تأمين الحج والعمرة:\n\nوتشمل التغطية التي تقدمها الحالات الصحية الطارئة، وإصابات كوفيد-19 الطارئة، والحوادث العامة والوفيات، وإلغاء أو تأخر رحلات الطيران المغادرة.",
                'details_en' => "Hajj and Umrah Insurance Document:\n\nThe coverage it provides includes emergency health cases, emergency COVID-19 injuries, general accidents and deaths, and cancellation or delay of departing flights.",
                'icon' => 'fas fa-kaaba',
                'color' => '#30cfd0',
                'image_url' => '/new/تامين الحج.png',
                'sort_order' => 7,
            ],
            [
                'title_ar' => 'تأمين وافدين للمقيمين',
                'title_en' => 'Expatriate Insurance for Residents',
                'description_ar' => 'وثيقة مدمجة وتحتوي على تغطيات المسؤولية المهنية والحوادث الشخصية لحماية العمالة الوافدة',
                'description_en' => 'Integrated document containing professional liability and personal accident coverage to protect expatriate workers',
                'details_ar' => "وثيقة تأمين وافدين للمقيمين:\n\nوثيقة مدمجة وتحتوي على تغطيات المسؤولية المهنية والحوادث الشخصية لحماية العمالة الوافدة.\n\nوثيقة المسؤولية المهنية:\nتغطي هذه الوثيقة المسؤولية المهنية للمهنيين نتيجة الأخطاء التي قد تحدث أثناء مزاولة المهنة، والتي قد تتسبب في أضرار جسدية أو مادية للغير (الطرف الثالث)، وذلك حسب نوعية المهنة.\n\nوثيقة الحوادث الشخصية:\nتغطي هذه الوثيقة الوفاة والعجز الدائم أو العجز الجزئي الدائم أو المؤقت بسبب حادث عرضي مفاجئ خلال أربعة وعشرون ساعة داخل أو خارج ليبيا وهي إما أن تكون فردية أو جماعية.",
                'details_en' => "Expatriate Insurance for Residents Document:\n\nIntegrated document containing professional liability and personal accident coverage to protect expatriate workers.\n\nProfessional Liability Document:\nThis document covers the professional liability of professionals resulting from errors that may occur during the practice of the profession, which may cause physical or material damage to others (third party), according to the type of profession.\n\nPersonal Accident Document:\nThis document covers death and permanent disability or partial permanent or temporary disability due to a sudden accidental accident within twenty-four hours inside or outside Libya, and it can be either individual or collective.",
                'icon' => 'fas fa-users',
                'color' => '#ff6b6b',
                'image_url' => '/new/تامين الوافدين.png',
                'sort_order' => 8,
            ],
        ];
        foreach ($types as $type) {
            DB::table('insurance_types')->insert(array_merge($type, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]));
        }
    }

    public function down(): void
    {
        DB::table('insurance_types')->truncate();
        DB::table('homepage_services')->truncate();
        DB::table('homepage_sliders')->truncate();
        DB::table('site_settings')->truncate();
    }
};
