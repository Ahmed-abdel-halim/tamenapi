-- ============================================================
-- سكريبت اللوحات (Plates) - تنفيذ يدوي بعد تشغيل سكريبت المدن
-- نفّذ 01_cities_manual.sql أو 01_cities_manual_no_unique.sql أولاً
-- ============================================================
-- الجدول: plates (id, plate_number, city_id, created_at, updated_at)
-- لكل مدينة: لوحة افتراضية برقم = order المدينة
-- ============================================================

-- إدراج لوحة افتراضية لكل مدينة (برقم = رقم اللوحة/order) إذا لم توجد
-- يتم الربط مع المدن عبر name_ar

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '1', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'سبها'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '2', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'البيضاء'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '3', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'مصراته'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '4', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الزاوية'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '5', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'طرابلس'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '6', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الخمس'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '7', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'سرت'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '8', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'بنغازي'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '9', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'زوارة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '10', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'درنة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '11', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الشاطئ'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '12', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'إجدابيا'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '13', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الجفرة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '14', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'طبرق'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '15', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'مرزق'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '16', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الواحات'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '17', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'غدامس'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '18', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'المرج'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '19', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'نالوت'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '20', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الكفرة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '21', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'غريان'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '22', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'يفرن'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '24', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'أوباري'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '25', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'سهل جفارة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '26', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'صبراتة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '27', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'ترهونة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '28', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'بني وليد'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '30', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الأبيار'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '32', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'غات'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '33', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'قصر بن غشير'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '34', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'جنزور'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '35', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'شحات'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '36', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'زليتن'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '37', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'باطن الجبل'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '38', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'مزدة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '40', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الجميل'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '41', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'العجيلات'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '42', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الزنتان'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '43', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'جادو'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '44', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'مسلاتة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '45', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الساحل بالجبل'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '46', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'القربوللي'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '47', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'صرمان'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '48', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'كاباو'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '49', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'القبة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '50', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الأصابعة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '51', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'رأس لانوف'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '52', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'توكرة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '53', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'كلة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '54', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الرحيبات'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '55', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الشاطئ الغربي'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '56', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الرجبان'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '57', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الحرابة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '58', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'جنوب المرج'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '59', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الشقيقة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '60', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الهلال'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '61', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'زلطن'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '62', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'القطرون'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '65', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'الرياينة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '66', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'سلوق وقمينس'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '67', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'أوجلة وجخرة'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

INSERT INTO `plates` (`plate_number`, `city_id`, `created_at`, `updated_at`)
SELECT '71', c.`id`, NOW(), NOW() FROM `cities` c WHERE c.`name_ar` = 'تراغن'
AND NOT EXISTS (SELECT 1 FROM `plates` p WHERE p.`city_id` = c.`id` LIMIT 1) LIMIT 1;

-- تحديث رقم اللوحة للمدن التي لديها لوحة قديمة (مطابقة لـ order الحالي)
UPDATE `plates` p
INNER JOIN `cities` c ON p.`city_id` = c.`id`
SET p.`plate_number` = CAST(c.`order` AS CHAR), p.`updated_at` = NOW()
WHERE p.`plate_number` != CAST(c.`order` AS CHAR);
