-- ============================================================
-- سكريبت المدن (Cities) - تنفيذ يدوي في قاعدة البيانات
-- نفّذ هذا الملف أولاً ثم 02_plates_manual.sql
-- ============================================================
-- قاعدة البيانات: MySQL / MariaDB
-- الجدول: cities (id, name_ar, name_en, order, created_at, updated_at)
-- ============================================================

-- تحديث أو إدراج المدن (حسب name_ar)
-- إذا كانت السجلات موجودة يتم التحديث، وإلا يتم الإدراج

INSERT INTO `cities` (`name_ar`, `name_en`, `order`, `created_at`, `updated_at`) VALUES
('سبها', 'Sabha', 1, NOW(), NOW()),
('البيضاء', 'Al Bayda', 2, NOW(), NOW()),
('مصراته', 'Misrata', 3, NOW(), NOW()),
('الزاوية', 'Zawiya', 4, NOW(), NOW()),
('طرابلس', 'Tripoli', 5, NOW(), NOW()),
('الخمس', 'Al Khums', 6, NOW(), NOW()),
('سرت', 'Sirte', 7, NOW(), NOW()),
('بنغازي', 'Benghazi', 8, NOW(), NOW()),
('زوارة', 'Zuwara', 9, NOW(), NOW()),
('درنة', 'Derna', 10, NOW(), NOW()),
('الشاطئ', 'Ash Shati', 11, NOW(), NOW()),
('إجدابيا', 'Ajdabiya', 12, NOW(), NOW()),
('الجفرة', 'Al Jufra', 13, NOW(), NOW()),
('طبرق', 'Tobruk', 14, NOW(), NOW()),
('مرزق', 'Murzuq', 15, NOW(), NOW()),
('الواحات', 'Al Wahat', 16, NOW(), NOW()),
('غدامس', 'Ghadames', 17, NOW(), NOW()),
('المرج', 'Al Marj', 18, NOW(), NOW()),
('نالوت', 'Nalut', 19, NOW(), NOW()),
('الكفرة', 'Al Kufra', 20, NOW(), NOW()),
('غريان', 'Gharyan', 21, NOW(), NOW()),
('يفرن', 'Yefren', 22, NOW(), NOW()),
('أوباري', 'Awbari', 24, NOW(), NOW()),
('سهل جفارة', 'Sahl Jafara', 25, NOW(), NOW()),
('صبراتة', 'Sabratha', 26, NOW(), NOW()),
('ترهونة', 'Tarhuna', 27, NOW(), NOW()),
('بني وليد', 'Bani Walid', 28, NOW(), NOW()),
('الأبيار', 'Al Abyar', 30, NOW(), NOW()),
('غات', 'Ghat', 32, NOW(), NOW()),
('قصر بن غشير', 'Qasr Bin Ghashir', 33, NOW(), NOW()),
('جنزور', 'Janzour', 34, NOW(), NOW()),
('شحات', 'Shahat', 35, NOW(), NOW()),
('زليتن', 'Zliten', 36, NOW(), NOW()),
('باطن الجبل', 'Batn Al Jabal', 37, NOW(), NOW()),
('مزدة', 'Mizda', 38, NOW(), NOW()),
('الجميل', 'Al Jameel', 40, NOW(), NOW()),
('العجيلات', 'Al Ajaylat', 41, NOW(), NOW()),
('الزنتان', 'Al Zintan', 42, NOW(), NOW()),
('جادو', 'Jadu', 43, NOW(), NOW()),
('مسلاتة', 'Msallata', 44, NOW(), NOW()),
('الساحل بالجبل', 'As Sahil Bil Jabal', 45, NOW(), NOW()),
('القربوللي', 'Al Qurbuli', 46, NOW(), NOW()),
('صرمان', 'Sorman', 47, NOW(), NOW()),
('كاباو', 'Kabao', 48, NOW(), NOW()),
('القبة', 'Al Qubah', 49, NOW(), NOW()),
('الأصابعة', 'Al Asabi\'a', 50, NOW(), NOW()),
('رأس لانوف', 'Ras Lanuf', 51, NOW(), NOW()),
('توكرة', 'Tokra', 52, NOW(), NOW()),
('كلة', 'Kikla', 53, NOW(), NOW()),
('الرحيبات', 'Al Rhybat', 54, NOW(), NOW()),
('الشاطئ الغربي', 'Western Shati', 55, NOW(), NOW()),
('الرجبان', 'Ar Rajban', 56, NOW(), NOW()),
('الحرابة', 'Al Haraba', 57, NOW(), NOW()),
('جنوب المرج', 'South Al Marj', 58, NOW(), NOW()),
('الشقيقة', 'Al Shiqiqah', 59, NOW(), NOW()),
('الهلال', 'Al Hilal', 60, NOW(), NOW()),
('زلطن', 'Zaltan', 61, NOW(), NOW()),
('القطرون', 'Al Qatron', 62, NOW(), NOW()),
('الرياينة', 'Ar Rayayina', 65, NOW(), NOW()),
('سلوق وقمينس', 'Sulug & Qaminis', 66, NOW(), NOW()),
('أوجلة وجخرة', 'Awjila & Jakhira', 67, NOW(), NOW()),
('تراغن', 'Tragin', 71, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `name_en` = VALUES(`name_en`),
  `order` = VALUES(`order`),
  `updated_at` = NOW();

-- ملاحظة: ON DUPLICATE KEY UPDATE يعمل فقط إذا كان هناك مفتاح فريد على name_ar.
-- إذا لم يكن لديك UNIQUE على name_ar، استخدم الملف البديل: 01_cities_manual_no_unique.sql
