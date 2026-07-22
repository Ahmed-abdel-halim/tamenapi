<?php

namespace App\Helpers;

class AgentPercentageHelper
{
    /**
     * Resolve agent percentage for a given document type and date.
     *
     * @param mixed $documentPercentages (array or json string or null)
     * @param string $docType (e.g. 'تأمين سيارات إجباري', 'تأمين المسافرين', etc.)
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

        // Map specific insurance document types to standard percentage keys
        $key = $docType;
        $carKeys = [
            'تأمين إجباري سيارات',
            'تأمين سيارة جمرك',
            'تأمين سيارات أجنبية',
            'تأمين طرف ثالث سيارات',
            'تأمين سيارات إجباري',
            'تأمين سيارات'
        ];

        if (in_array($docType, $carKeys)) {
            $key = 'تأمين سيارات';
        }

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

                if (($pType === $key || $pType === $docType) && !empty($pStart) && !empty($pEnd)) {
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
                if (isset($monthData[$key])) return (float) $monthData[$key];
                if (isset($monthData[$docType])) return (float) $monthData[$docType];
                if ($key === 'تأمين سيارات' && isset($monthData['تأمين سيارات إجباري'])) return (float) $monthData['تأمين سيارات إجباري'];
            }
        }

        // 3. Check default nested structure { default: { ... } }
        if (isset($documentPercentages['default']) && is_array($documentPercentages['default'])) {
            $def = $documentPercentages['default'];
            if (isset($def[$key])) return (float) $def[$key];
            if (isset($def[$docType])) return (float) $def[$docType];
            if ($key === 'تأمين سيارات' && isset($def['تأمين سيارات إجباري'])) return (float) $def['تأمين سيارات إجباري'];
            if (isset($def['تأمين سيارات'])) return (float) $def['تأمين سيارات'];
        }

        // 4. Check flat format (object: { "تأمين سيارات": 50 })
        if (isset($documentPercentages[$key]) && is_numeric($documentPercentages[$key])) return (float) $documentPercentages[$key];
        if (isset($documentPercentages[$docType]) && is_numeric($documentPercentages[$docType])) return (float) $documentPercentages[$docType];
        if ($key === 'تأمين سيارات' && isset($documentPercentages['تأمين سيارات إجباري']) && is_numeric($documentPercentages['تأمين سيارات إجباري'])) {
            return (float) $documentPercentages['تأمين سيارات إجباري'];
        }

        // 5. Check indexed array format [{ document_type: '...', percentage: 50 }]
        foreach ($documentPercentages as $k => $val) {
            if (is_array($val) && isset($val['document_type'])) {
                if ($val['document_type'] === $key || $val['document_type'] === $docType) {
                    return (float) ($val['percentage'] ?? 0);
                }
            }
        }

        return 0.0;
    }
}
