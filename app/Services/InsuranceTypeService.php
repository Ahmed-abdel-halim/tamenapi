<?php

namespace App\Services;

class InsuranceTypeService
{
    // تعريف موحد لجميع أنواع التأمين
    const INSURANCE_TYPES = [
        'car_mandatory' => 'تأمين إجباري سيارات',
        'car_customs' => 'تأمين سيارة جمرك',
        'car_foreign' => 'تأمين سيارات أجنبية',
        'car_third_party' => 'تأمين طرف ثالث سيارات',
        'car_international' => 'تأمين السيارات الدولي',
        'travel' => 'تأمين المسافرين',
        'resident' => 'تأمين الوافدين',
        'marine' => 'تأمين الهياكل البحرية',
        'professional' => 'تأمين المسؤولية المهنية',
        'personal_accident' => 'تأمين الحوادث الشخصية',
    ];

    // تجميع أنواع تأمين السيارات
    const CAR_INSURANCE_TYPES = [
        'car_mandatory',
        'car_customs',
        'car_foreign',
        'car_third_party',
    ];

    // أسماء بديلة محتملة (للمعالجة)
    const ALIASES = [
        'تأمين إجباري سيارات' => 'car_mandatory',
        'تأمين سيارات إجباري' => 'car_mandatory',
        'تأمين سيارات اجباري' => 'car_mandatory',
        'تأمين السيارات الدولي' => 'car_international',
        'تأمين سيارات دولي' => 'car_international',
        'تأمين سيارات دولي' => 'car_international',
    ];

    /**
     * تحويل اسم التأمين إلى المفتاح الموحد
     */
    public static function normalizeInsuranceType(string $type): string
    {
        // التحقق من الأسماء البديلة أولاً
        if (isset(self::ALIASES[$type])) {
            return self::ALIASES[$type];
        }

        // البحث في القيم
        $key = array_search($type, self::INSURANCE_TYPES);
        return $key !== false ? $key : $type;
    }

    /**
     * الحصول على الاسم المعروض
     */
    public static function getDisplayName(string $key): string
    {
        return self::INSURANCE_TYPES[$key] ?? $key;
    }

    /**
     * التحقق من نوع تأمين سيارات
     */
    public static function isCarInsurance(string $type): bool
    {
        $normalized = self::normalizeInsuranceType($type);
        return in_array($normalized, self::CAR_INSURANCE_TYPES) || 
               $normalized === 'car_international';
    }

    /**
     * الحصول على جميع أنواع تأمين السيارات
     */
    public static function getCarInsuranceTypes(): array
    {
        return array_map(function($key) {
            return self::INSURANCE_TYPES[$key];
        }, self::CAR_INSURANCE_TYPES);
    }

    /**
     * التحقق من تطابق نوع التأمين مع الفلتر
     */
    public static function matchesFilter(string $docType, string $filter): bool
    {
        if (!$filter || $filter === 'all') {
            return true;
        }

        $normalizedDoc = self::normalizeInsuranceType($docType);
        $normalizedFilter = self::normalizeInsuranceType($filter);

        // إذا كان الفلتر هو "تأمين السيارات" (جميع أنواع السيارات)
        if ($filter === 'تأمين السيارات' || $normalizedFilter === 'car_insurance') {
            return self::isCarInsurance($docType);
        }

        // تطابق مباشر
        return $normalizedDoc === $normalizedFilter || $docType === $filter;
    }

    /**
     * الحصول على جميع أنواع التأمين المتاحة
     */
    public static function getAllTypes(): array
    {
        return array_values(self::INSURANCE_TYPES);
    }
}

