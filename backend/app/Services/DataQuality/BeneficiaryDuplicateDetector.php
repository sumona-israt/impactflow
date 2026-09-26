<?php

namespace App\Services\DataQuality;

use App\Enums\DataQualityIssueStatus;
use App\Models\Beneficiary;
use App\Models\DataQualityIssue;

/**
 * Duplicates are flagged, not blocked (docs/database-design.md §12) — this
 * runs after a beneficiary is created/updated (see CreateBeneficiaryAction,
 * UpdateBeneficiaryAction) and after a bulk-import commit (which reuses
 * CreateBeneficiaryAction), so there's one detector for both paths.
 */
class BeneficiaryDuplicateDetector
{
    private const ISSUE_TYPE = 'duplicate_beneficiary';

    public function detect(Beneficiary $beneficiary): void
    {
        $name = BeneficiaryFieldNormalizer::name($beneficiary->full_name);
        $phone = BeneficiaryFieldNormalizer::phone($beneficiary->phone);

        // Matching requires both a name and a phone on the beneficiary being
        // checked — an empty phone can't be treated as "matches" every other
        // beneficiary without a phone.
        if ($name === '' || $phone === '') {
            return;
        }

        $match = Beneficiary::query()
            ->where('id', '!=', $beneficiary->id)
            ->whereRaw('lower(trim(full_name)) = ?', [$name])
            ->get(['id', 'full_name', 'phone'])
            ->first(fn (Beneficiary $candidate) => BeneficiaryFieldNormalizer::phone($candidate->phone) === $phone);

        if ($match === null) {
            return;
        }

        $alreadyOpen = DataQualityIssue::query()
            ->where('entity_type', Beneficiary::class)
            ->where('entity_id', $beneficiary->id)
            ->where('issue_type', self::ISSUE_TYPE)
            ->where('status', DataQualityIssueStatus::Open)
            ->exists();

        if ($alreadyOpen) {
            return;
        }

        DataQualityIssue::create([
            'entity_type' => Beneficiary::class,
            'entity_id' => $beneficiary->id,
            'issue_type' => self::ISSUE_TYPE,
            'severity' => 'warning',
            'description' => "Possible duplicate of \"{$match->full_name}\" (ID {$match->id}) — matching name and phone.",
            'status' => DataQualityIssueStatus::Open,
            'detected_at' => now(),
        ]);
    }
}
