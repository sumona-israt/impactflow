<?php

namespace App\Services\DataQuality;

/**
 * Shared by BeneficiaryDuplicateDetector (post-create/update) and
 * PreviewDataImportAction (pre-create, from a spreadsheet row) so the two
 * duplicate heuristics can't drift apart — see docs/database-design.md §9.
 */
class BeneficiaryFieldNormalizer
{
    public static function name(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    public static function phone(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }
}
