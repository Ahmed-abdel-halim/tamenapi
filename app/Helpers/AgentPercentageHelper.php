<?php

namespace App\Helpers;

class AgentPercentageHelper
{
    /**
     * Resolve agent percentage for a given document type and date.
     *
     * @param mixed $documentPercentages (array or json string or null)
     * @param string $docType (e.g. 'تأمين سيارات إجباري', 'تأمين السيارات الدولي', 'تأمين سيارات دولي', etc.)
     * @param string|null $docDate (e.g. '2025-01-10' or '2025-01-10 14:30:00')
     * @return float
     */
    public static function resolvePercentage($documentPercentages, string $docType, ?string $docDate = null): float
    {
        if (empty($documentPercentages)) {
            return 0.0;
        }

        if (is_string($documentPercentages)) {
            $decoded = json_decode($documentPercentages, true);
            $documentPercentages = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($documentPercentages)) {
            return 0.0;
        }

        // Generate list of candidate keys to search for this document type
        $candidateKeys = self::getCandidateKeys($docType);

        $formattedDate = null;
        $monthKey = null;
        if (!empty($docDate)) {
            $timestamp = strtotime($docDate);
            if ($timestamp !== false) {
                $formattedDate = date('Y-m-d', $timestamp);
                $monthKey = date('Y-m', $timestamp);
            }
        }

        // 1. Check period_overrides (exact date range: start_date <= docDate <= end_date)
        if ($formattedDate && isset($documentPercentages['period_overrides']) && is_array($documentPercentages['period_overrides'])) {
            foreach ($documentPercentages['period_overrides'] as $period) {
                if (!is_array($period)) continue;
                $pType = $period['doc_type'] ?? '';
                $pStart = $period['start_date'] ?? '';
                $pEnd = $period['end_date'] ?? '';

                if (in_array($pType, $candidateKeys) && !empty($pStart) && !empty($pEnd)) {
                    if ($formattedDate >= $pStart && $formattedDate <= $pEnd) {
                        return (float) ($period['percentage'] ?? 0);
                    }
                }
            }
        }

        // 2. Check monthly_overrides (YYYY-MM)
        if ($monthKey && isset($documentPercentages['monthly_overrides']) && is_array($documentPercentages['monthly_overrides'])) {
            if (isset($documentPercentages['monthly_overrides'][$monthKey]) && is_array($documentPercentages['monthly_overrides'][$monthKey])) {
                $monthData = $documentPercentages['monthly_overrides'][$monthKey];
                foreach ($candidateKeys as $cKey) {
                    if (isset($monthData[$cKey]) && is_numeric($monthData[$cKey])) {
                        return (float) $monthData[$cKey];
                    }
                }
            }
        }

        // 3. Check default nested structure { default: { ... } }
        if (isset($documentPercentages['default']) && is_array($documentPercentages['default'])) {
            $def = $documentPercentages['default'];
            foreach ($candidateKeys as $cKey) {
                if (isset($def[$cKey]) && is_numeric($def[$cKey])) {
                    return (float) $def[$cKey];
                }
            }
        }

        // 4. Check flat format (object: { "تأمين سيارات دولي": 50 })
        foreach ($candidateKeys as $cKey) {
            if (isset($documentPercentages[$cKey]) && is_numeric($documentPercentages[$cKey])) {
                return (float) $documentPercentages[$cKey];
            }
        }

        // 5. Check indexed array format [{ document_type: '...', percentage: 50 }]
        foreach ($documentPercentages as $k => $val) {
            if (is_array($val) && isset($val['document_type'])) {
                if (in_array($val['document_type'], $candidateKeys)) {
                    return (float) ($val['percentage'] ?? 0);
                }
            }
        }

        return 0.0;
    }

    /**
     * Get candidate keys for a given document type.
     */
    private static function getCandidateKeys(string $docType): array
    {
        $docTypeClean = trim($docType);

        // International Car Insurance
        $internationalKeys = [
            'تأمين سيارات دولي',
            'تأمين السيارات الدولي',
            'تأمين دولي',
            'خدمات تامين السيارات الدولي تونس',
            'car_international',
            'InternationalInsuranceDocument'
        ];
        if (in_array($docTypeClean, $internationalKeys)) {
            return $internationalKeys;
        }

        // Obligatory / Local Car Insurance
        $carKeys = [
            'تأمين سيارات',
            'تأمين سيارات إجباري',
            'تأمين إجباري سيارات',
            'تأمين سيارة جمرك',
            'تأمين سيارات أجنبية',
            'تأمين طرف ثالث سيارات',
            'InsuranceDocument'
        ];
        if (in_array($docTypeClean, $carKeys)) {
            return array_unique(array_merge([$docTypeClean], $carKeys));
        }

        // Travel Insurance
        $travelKeys = [
            'تأمين المسافرين',
            'تأمين زائرين ليبيا',
            'تأمين السفر',
            'TravelInsuranceDocument'
        ];
        if (in_array($docTypeClean, $travelKeys)) {
            return $travelKeys;
        }

        // Resident Insurance
        $residentKeys = [
            'تأمين الوافدين',
            'ResidentInsuranceDocument'
        ];
        if (in_array($docTypeClean, $residentKeys)) {
            return $residentKeys;
        }

        // Marine Structure Insurance
        $marineKeys = [
            'تأمين الهياكل البحرية',
            'MarineStructureInsuranceDocument'
        ];
        if (in_array($docTypeClean, $marineKeys)) {
            return $marineKeys;
        }

        // Professional Liability Insurance
        $professionalKeys = [
            'تأمين المسؤولية المهنية (الطبية)',
            'تأمين المسؤولية المهنية',
            'ProfessionalLiabilityInsuranceDocument'
        ];
        if (in_array($docTypeClean, $professionalKeys)) {
            return $professionalKeys;
        }

        // Personal Accident Insurance
        $accidentKeys = [
            'تأمين الحوادث الشخصية',
            'PersonalAccidentInsuranceDocument'
        ];
        if (in_array($docTypeClean, $accidentKeys)) {
            return $accidentKeys;
        }

        // School Student Insurance
        $schoolKeys = [
            'تأمين طلبة المدارس',
            'تأمين حماية طلاب المدارس',
            'SchoolStudentInsuranceDocument'
        ];
        if (in_array($docTypeClean, $schoolKeys)) {
            return $schoolKeys;
        }

        // Cargo Insurance
        $cargoKeys = [
            'تأمين البضائع',
            'تأمين شحن البضائع',
            'CargoInsuranceDocument'
        ];
        if (in_array($docTypeClean, $cargoKeys)) {
            return $cargoKeys;
        }

        // Cash In Transit Insurance
        $cashKeys = [
            'تأمين نقل النقدية',
            'CashInTransitInsuranceDocument'
        ];
        if (in_array($docTypeClean, $cashKeys)) {
            return $cashKeys;
        }

        // Fallback: return the docType clean and common variations
        return array_unique([
            $docTypeClean,
            str_replace('السيارات', 'سيارات', $docTypeClean),
            str_replace('سيارات', 'السيارات', $docTypeClean)
        ]);
    }
}
