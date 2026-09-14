<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class InternationalInsuranceHelper
{
    /**
     * Deduplicate international insurance documents.
     * Prevents duplicate counting of policies that exist both as a temporary local draft (e.g. LBY0014, LBY0017)
     * and as an official Union card (e.g. LBY/7130952, LBY/7130953).
     *
     * @param iterable $documents
     * @return Collection|array
     */
    public static function deduplicateDocuments($documents)
    {
        $isCollection = $documents instanceof Collection;
        $items = $isCollection ? $documents->all() : (is_array($documents) ? $documents : iterator_to_array($documents));

        if (count($items) <= 1) {
            return $documents;
        }

        $officialCards = [];
        $tempDrafts = [];
        $otherDocs = [];

        foreach ($items as $doc) {
            $docNumber = self::getProp($doc, 'document_number') ?? self::getProp($doc, 'insurance_code') ?? '';
            $docNumber = trim((string)$docNumber);

            // Official Union cards have a slash (e.g., LBY/7130953)
            if (strpos($docNumber, '/') !== false) {
                $officialCards[] = $doc;
            } elseif (preg_match('/^LBY\d+$/i', $docNumber)) {
                // Temporary local auto-generated format like LBY0014, LBY0017
                $tempDrafts[] = $doc;
            } else {
                $otherDocs[] = $doc;
            }
        }

        // If there are no temporary drafts, return deduplicated official cards
        if (empty($tempDrafts)) {
            return self::deduplicateIdentical($items, $isCollection);
        }

        // Index official cards for fast and reliable lookup
        $officialByDocNumber = [];
        $officialByExternalId = [];
        $officialByChassis = [];
        $officialByPhoneName = [];

        $uniqueOfficial = [];
        $seenOfficialDocNumbers = [];

        foreach ($officialCards as $off) {
            $num = trim((string)(self::getProp($off, 'document_number') ?? self::getProp($off, 'insurance_code') ?? ''));
            if ($num !== '') {
                if (isset($seenOfficialDocNumbers[$num])) {
                    // Exact duplicate official card, skip
                    continue;
                }
                $seenOfficialDocNumbers[$num] = true;
                $officialByDocNumber[$num] = true;
            }

            $uniqueOfficial[] = $off;

            $extId = trim((string)(self::getProp($off, 'external_policy_number') ?? ''));
            if ($extId !== '') {
                $officialByExternalId[$extId] = true;
            }

            $chassis = self::normalizeChassis(self::getProp($off, 'chassis_number'));
            if ($chassis !== '') {
                $officialByChassis[$chassis] = true;
            }

            $phone = self::normalizePhone(self::getProp($off, 'phone'));
            $name = self::normalizeName(self::getProp($off, 'insured_name') ?? self::getProp($off, 'name'));
            if ($phone !== '' && $name !== '') {
                $officialByPhoneName[$phone . '_' . $name] = true;
            }
        }

        // Filter temporary drafts: only keep if they do NOT match any official card
        $keptDrafts = [];
        foreach ($tempDrafts as $draft) {
            $extId = trim((string)(self::getProp($draft, 'external_policy_number') ?? ''));
            if ($extId !== '' && (isset($officialByExternalId[$extId]) || isset($officialByDocNumber[$extId]))) {
                continue; // Duplicate of an official card by external_policy_number!
            }

            $chassis = self::normalizeChassis(self::getProp($draft, 'chassis_number'));
            if ($chassis !== '' && isset($officialByChassis[$chassis])) {
                continue; // Duplicate of an official card by chassis number!
            }

            $phone = self::normalizePhone(self::getProp($draft, 'phone'));
            $name = self::normalizeName(self::getProp($draft, 'insured_name') ?? self::getProp($draft, 'name'));
            if ($phone !== '' && $name !== '' && isset($officialByPhoneName[$phone . '_' . $name])) {
                continue; // Duplicate of an official card by phone and name!
            }

            $keptDrafts[] = $draft;
        }

        $result = array_merge($uniqueOfficial, $keptDrafts, $otherDocs);

        return $isCollection ? collect($result) : $result;
    }

    public static function getProp($obj, string $prop)
    {
        if (is_object($obj)) {
            return $obj->$prop ?? null;
        }
        if (is_array($obj)) {
            return $obj[$prop] ?? null;
        }
        return null;
    }

    public static function normalizeChassis($chassis): string
    {
        if (!$chassis) return '';
        $clean = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(trim((string)$chassis)));
        if (strlen($clean) < 4 || preg_match('/^0+$/', $clean)) {
            return '';
        }
        return $clean;
    }

    public static function normalizePhone($phone): string
    {
        if (!$phone) return '';
        $digits = preg_replace('/\D/', '', (string)$phone);
        // Ignore trivial phones like 0, 00, 000, etc.
        if (strlen($digits) < 5 || preg_match('/^0+$/', $digits)) {
            return '';
        }
        return substr($digits, -9); // last 9 digits
    }

    public static function normalizeName($name): string
    {
        if (!$name) return '';
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', '', (string)$name);
        $clean = preg_replace('/\s+/', ' ', trim($clean));
        return mb_strtolower($clean, 'UTF-8');
    }

    private static function deduplicateIdentical(array $items, bool $isCollection)
    {
        $seen = [];
        $unique = [];
        foreach ($items as $item) {
            $num = trim((string)(self::getProp($item, 'document_number') ?? self::getProp($item, 'insurance_code') ?? ''));
            $chassis = self::normalizeChassis(self::getProp($item, 'chassis_number'));
            $key = $num ?: ($chassis ?: spl_object_hash((object)$item));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $item;
        }
        return $isCollection ? collect($unique) : $unique;
    }
}
