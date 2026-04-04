-- ============================================================
-- سكريبت المدن (بدون الحاجة لمفتاح فريد على name_ar)
-- استخدم هذا الملف إذا لم يكن على cities مفتاح UNIQUE لـ name_ar
-- نفّذ هذا الملف أولاً ثم 02_plates_manual.sql
-- ============================================================

-- 1) إدراج المدن التي لا exist (حسب name_ar)
INSERT INTO `cities` (`name_ar`, `name_en`, `order`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'سبها' AS name_ar, 'Sabha' AS name_en, 1 AS `order`, NOW() AS created_at, NOW() AS updated_at UNION ALL
  SELECT 'البيضاء', 'Al Bayda', 2, NOW(), NOW() UNION ALL
  SELECT 'مصراته', 'Misrata', 3, NOW(), NOW() UNION ALL
  SELECT 'الزاوية', 'Zawiya', 4, NOW(), NOW() UNION ALL
  SELECT 'طرابلس', 'Tripoli', 5, NOW(), NOW() UNION ALL
  SELECT 'الخمس', 'Al Khums', 6, NOW(), NOW() UNION ALL
  SELECT 'سرت', 'Sirte', 7, NOW(), NOW() UNION ALL
  SELECT 'بنغازي', 'Benghazi', 8, NOW(), NOW() UNION ALL
  SELECT 'زوارة', 'Zuwara', 9, NOW(), NOW() UNION ALL
  SELECT 'درنة', 'Derna', 10, NOW(), NOW() UNION ALL
  SELECT 'الشاطئ', 'Ash Shati', 11, NOW(), NOW() UNION ALL
  SELECT 'إجدابيا', 'Ajdabiya', 12, NOW(), NOW() UNION ALL
  SELECT 'الجفرة', 'Al Jufra', 13, NOW(), NOW() UNION ALL
  SELECT 'طبرق', 'Tobruk', 14, NOW(), NOW() UNION ALL
  SELECT 'مرزق', 'Murzuq', 15, NOW(), NOW() UNION ALL
  SELECT 'الواحات', 'Al Wahat', 16, NOW(), NOW() UNION ALL
  SELECT 'غدامس', 'Ghadames', 17, NOW(), NOW() UNION ALL
  SELECT 'المرج', 'Al Marj', 18, NOW(), NOW() UNION ALL
  SELECT 'نالوت', 'Nalut', 19, NOW(), NOW() UNION ALL
  SELECT 'الكفرة', 'Al Kufra', 20, NOW(), NOW() UNION ALL
  SELECT 'غريان', 'Gharyan', 21, NOW(), NOW() UNION ALL
  SELECT 'يفرن', 'Yefren', 22, NOW(), NOW() UNION ALL
  SELECT 'أوباري', 'Awbari', 24, NOW(), NOW() UNION ALL
  SELECT 'سهل جفارة', 'Sahl Jafara', 25, NOW(), NOW() UNION ALL
  SELECT 'صبراتة', 'Sabratha', 26, NOW(), NOW() UNION ALL
  SELECT 'ترهونة', 'Tarhuna', 27, NOW(), NOW() UNION ALL
  SELECT 'بني وليد', 'Bani Walid', 28, NOW(), NOW() UNION ALL
  SELECT 'الأبيار', 'Al Abyar', 30, NOW(), NOW() UNION ALL
  SELECT 'غات', 'Ghat', 32, NOW(), NOW() UNION ALL
  SELECT 'قصر بن غشير', 'Qasr Bin Ghashir', 33, NOW(), NOW() UNION ALL
  SELECT 'جنزور', 'Janzour', 34, NOW(), NOW() UNION ALL
  SELECT 'شحات', 'Shahat', 35, NOW(), NOW() UNION ALL
  SELECT 'زليتن', 'Zliten', 36, NOW(), NOW() UNION ALL
  SELECT 'باطن الجبل', 'Batn Al Jabal', 37, NOW(), NOW() UNION ALL
  SELECT 'مزدة', 'Mizda', 38, NOW(), NOW() UNION ALL
  SELECT 'الجميل', 'Al Jameel', 40, NOW(), NOW() UNION ALL
  SELECT 'العجيلات', 'Al Ajaylat', 41, NOW(), NOW() UNION ALL
  SELECT 'الزنتان', 'Al Zintan', 42, NOW(), NOW() UNION ALL
  SELECT 'جادو', 'Jadu', 43, NOW(), NOW() UNION ALL
  SELECT 'مسلاتة', 'Msallata', 44, NOW(), NOW() UNION ALL
  SELECT 'الساحل بالجبل', 'As Sahil Bil Jabal', 45, NOW(), NOW() UNION ALL
  SELECT 'القربوللي', 'Al Qurbuli', 46, NOW(), NOW() UNION ALL
  SELECT 'صرمان', 'Sorman', 47, NOW(), NOW() UNION ALL
  SELECT 'كاباو', 'Kabao', 48, NOW(), NOW() UNION ALL
  SELECT 'القبة', 'Al Qubah', 49, NOW(), NOW() UNION ALL
  SELECT 'الأصابعة', 'Al Asabi\'a', 50, NOW(), NOW() UNION ALL
  SELECT 'رأس لانوف', 'Ras Lanuf', 51, NOW(), NOW() UNION ALL
  SELECT 'توكرة', 'Tokra', 52, NOW(), NOW() UNION ALL
  SELECT 'كلة', 'Kikla', 53, NOW(), NOW() UNION ALL
  SELECT 'الرحيبات', 'Al Rhybat', 54, NOW(), NOW() UNION ALL
  SELECT 'الشاطئ الغربي', 'Western Shati', 55, NOW(), NOW() UNION ALL
  SELECT 'الرجبان', 'Ar Rajban', 56, NOW(), NOW() UNION ALL
  SELECT 'الحرابة', 'Al Haraba', 57, NOW(), NOW() UNION ALL
  SELECT 'جنوب المرج', 'South Al Marj', 58, NOW(), NOW() UNION ALL
  SELECT 'الشقيقة', 'Al Shiqiqah', 59, NOW(), NOW() UNION ALL
  SELECT 'الهلال', 'Al Hilal', 60, NOW(), NOW() UNION ALL
  SELECT 'زلطن', 'Zaltan', 61, NOW(), NOW() UNION ALL
  SELECT 'القطرون', 'Al Qatron', 62, NOW(), NOW() UNION ALL
  SELECT 'الرياينة', 'Ar Rayayina', 65, NOW(), NOW() UNION ALL
  SELECT 'سلوق وقمينس', 'Sulug & Qaminis', 66, NOW(), NOW() UNION ALL
  SELECT 'أوجلة وجخرة', 'Awjila & Jakhira', 67, NOW(), NOW() UNION ALL
  SELECT 'تراغن', 'Tragin', 71, NOW(), NOW()
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `cities` c WHERE c.`name_ar` = tmp.name_ar);

-- 2) تحديث order و name_en للمدن الموجودة
UPDATE `cities` SET `name_en`='Sabha', `order`=1, `updated_at`=NOW() WHERE `name_ar`='سبها';
UPDATE `cities` SET `name_en`='Al Bayda', `order`=2, `updated_at`=NOW() WHERE `name_ar`='البيضاء';
UPDATE `cities` SET `name_en`='Misrata', `order`=3, `updated_at`=NOW() WHERE `name_ar`='مصراته';
UPDATE `cities` SET `name_en`='Zawiya', `order`=4, `updated_at`=NOW() WHERE `name_ar`='الزاوية';
UPDATE `cities` SET `name_en`='Tripoli', `order`=5, `updated_at`=NOW() WHERE `name_ar`='طرابلس';
UPDATE `cities` SET `name_en`='Al Khums', `order`=6, `updated_at`=NOW() WHERE `name_ar`='الخمس';
UPDATE `cities` SET `name_en`='Sirte', `order`=7, `updated_at`=NOW() WHERE `name_ar`='سرت';
UPDATE `cities` SET `name_en`='Benghazi', `order`=8, `updated_at`=NOW() WHERE `name_ar`='بنغازي';
UPDATE `cities` SET `name_en`='Zuwara', `order`=9, `updated_at`=NOW() WHERE `name_ar`='زوارة';
UPDATE `cities` SET `name_en`='Derna', `order`=10, `updated_at`=NOW() WHERE `name_ar`='درنة';
UPDATE `cities` SET `name_en`='Ash Shati', `order`=11, `updated_at`=NOW() WHERE `name_ar`='الشاطئ';
UPDATE `cities` SET `name_en`='Ajdabiya', `order`=12, `updated_at`=NOW() WHERE `name_ar`='إجدابيا';
UPDATE `cities` SET `name_en`='Al Jufra', `order`=13, `updated_at`=NOW() WHERE `name_ar`='الجفرة';
UPDATE `cities` SET `name_en`='Tobruk', `order`=14, `updated_at`=NOW() WHERE `name_ar`='طبرق';
UPDATE `cities` SET `name_en`='Murzuq', `order`=15, `updated_at`=NOW() WHERE `name_ar`='مرزق';
UPDATE `cities` SET `name_en`='Al Wahat', `order`=16, `updated_at`=NOW() WHERE `name_ar`='الواحات';
UPDATE `cities` SET `name_en`='Ghadames', `order`=17, `updated_at`=NOW() WHERE `name_ar`='غدامس';
UPDATE `cities` SET `name_en`='Al Marj', `order`=18, `updated_at`=NOW() WHERE `name_ar`='المرج';
UPDATE `cities` SET `name_en`='Nalut', `order`=19, `updated_at`=NOW() WHERE `name_ar`='نالوت';
UPDATE `cities` SET `name_en`='Al Kufra', `order`=20, `updated_at`=NOW() WHERE `name_ar`='الكفرة';
UPDATE `cities` SET `name_en`='Gharyan', `order`=21, `updated_at`=NOW() WHERE `name_ar`='غريان';
UPDATE `cities` SET `name_en`='Yefren', `order`=22, `updated_at`=NOW() WHERE `name_ar`='يفرن';
UPDATE `cities` SET `name_en`='Awbari', `order`=24, `updated_at`=NOW() WHERE `name_ar`='أوباري';
UPDATE `cities` SET `name_en`='Sahl Jafara', `order`=25, `updated_at`=NOW() WHERE `name_ar`='سهل جفارة';
UPDATE `cities` SET `name_en`='Sabratha', `order`=26, `updated_at`=NOW() WHERE `name_ar`='صبراتة';
UPDATE `cities` SET `name_en`='Tarhuna', `order`=27, `updated_at`=NOW() WHERE `name_ar`='ترهونة';
UPDATE `cities` SET `name_en`='Bani Walid', `order`=28, `updated_at`=NOW() WHERE `name_ar`='بني وليد';
UPDATE `cities` SET `name_en`='Al Abyar', `order`=30, `updated_at`=NOW() WHERE `name_ar`='الأبيار';
UPDATE `cities` SET `name_en`='Ghat', `order`=32, `updated_at`=NOW() WHERE `name_ar`='غات';
UPDATE `cities` SET `name_en`='Qasr Bin Ghashir', `order`=33, `updated_at`=NOW() WHERE `name_ar`='قصر بن غشير';
UPDATE `cities` SET `name_en`='Janzour', `order`=34, `updated_at`=NOW() WHERE `name_ar`='جنزور';
UPDATE `cities` SET `name_en`='Shahat', `order`=35, `updated_at`=NOW() WHERE `name_ar`='شحات';
UPDATE `cities` SET `name_en`='Zliten', `order`=36, `updated_at`=NOW() WHERE `name_ar`='زليتن';
UPDATE `cities` SET `name_en`='Batn Al Jabal', `order`=37, `updated_at`=NOW() WHERE `name_ar`='باطن الجبل';
UPDATE `cities` SET `name_en`='Mizda', `order`=38, `updated_at`=NOW() WHERE `name_ar`='مزدة';
UPDATE `cities` SET `name_en`='Al Jameel', `order`=40, `updated_at`=NOW() WHERE `name_ar`='الجميل';
UPDATE `cities` SET `name_en`='Al Ajaylat', `order`=41, `updated_at`=NOW() WHERE `name_ar`='العجيلات';
UPDATE `cities` SET `name_en`='Al Zintan', `order`=42, `updated_at`=NOW() WHERE `name_ar`='الزنتان';
UPDATE `cities` SET `name_en`='Jadu', `order`=43, `updated_at`=NOW() WHERE `name_ar`='جادو';
UPDATE `cities` SET `name_en`='Msallata', `order`=44, `updated_at`=NOW() WHERE `name_ar`='مسلاتة';
UPDATE `cities` SET `name_en`='As Sahil Bil Jabal', `order`=45, `updated_at`=NOW() WHERE `name_ar`='الساحل بالجبل';
UPDATE `cities` SET `name_en`='Al Qurbuli', `order`=46, `updated_at`=NOW() WHERE `name_ar`='القربوللي';
UPDATE `cities` SET `name_en`='Sorman', `order`=47, `updated_at`=NOW() WHERE `name_ar`='صرمان';
UPDATE `cities` SET `name_en`='Kabao', `order`=48, `updated_at`=NOW() WHERE `name_ar`='كاباو';
UPDATE `cities` SET `name_en`='Al Qubah', `order`=49, `updated_at`=NOW() WHERE `name_ar`='القبة';
UPDATE `cities` SET `name_en`='Al Asabi\'a', `order`=50, `updated_at`=NOW() WHERE `name_ar`='الأصابعة';
UPDATE `cities` SET `name_en`='Ras Lanuf', `order`=51, `updated_at`=NOW() WHERE `name_ar`='رأس لانوف';
UPDATE `cities` SET `name_en`='Tokra', `order`=52, `updated_at`=NOW() WHERE `name_ar`='توكرة';
UPDATE `cities` SET `name_en`='Kikla', `order`=53, `updated_at`=NOW() WHERE `name_ar`='كلة';
UPDATE `cities` SET `name_en`='Al Rhybat', `order`=54, `updated_at`=NOW() WHERE `name_ar`='الرحيبات';
UPDATE `cities` SET `name_en`='Western Shati', `order`=55, `updated_at`=NOW() WHERE `name_ar`='الشاطئ الغربي';
UPDATE `cities` SET `name_en`='Ar Rajban', `order`=56, `updated_at`=NOW() WHERE `name_ar`='الرجبان';
UPDATE `cities` SET `name_en`='Al Haraba', `order`=57, `updated_at`=NOW() WHERE `name_ar`='الحرابة';
UPDATE `cities` SET `name_en`='South Al Marj', `order`=58, `updated_at`=NOW() WHERE `name_ar`='جنوب المرج';
UPDATE `cities` SET `name_en`='Al Shiqiqah', `order`=59, `updated_at`=NOW() WHERE `name_ar`='الشقيقة';
UPDATE `cities` SET `name_en`='Al Hilal', `order`=60, `updated_at`=NOW() WHERE `name_ar`='الهلال';
UPDATE `cities` SET `name_en`='Zaltan', `order`=61, `updated_at`=NOW() WHERE `name_ar`='زلطن';
UPDATE `cities` SET `name_en`='Al Qatron', `order`=62, `updated_at`=NOW() WHERE `name_ar`='القطرون';
UPDATE `cities` SET `name_en`='Ar Rayayina', `order`=65, `updated_at`=NOW() WHERE `name_ar`='الرياينة';
UPDATE `cities` SET `name_en`='Sulug & Qaminis', `order`=66, `updated_at`=NOW() WHERE `name_ar`='سلوق وقمينس';
UPDATE `cities` SET `name_en`='Awjila & Jakhira', `order`=67, `updated_at`=NOW() WHERE `name_ar`='أوجلة وجخرة';
UPDATE `cities` SET `name_en`='Tragin', `order`=71, `updated_at`=NOW() WHERE `name_ar`='تراغن';
