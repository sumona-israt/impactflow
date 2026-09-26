<?php

namespace App\Models;

use App\Enums\DataQualityIssueStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DataQualityIssue extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_type', 'entity_id', 'issue_type', 'severity', 'description',
        'status', 'detected_at', 'resolved_at', 'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DataQualityIssueStatus::class,
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Shared by DataQualityIssueController::score() and the executive
     * dashboard (see docs/database-design.md §9 for the formula's reasoning).
     *
     * @return array{score: float, total_beneficiaries: int, open_issues: int}
     */
    public static function computeScore(): array
    {
        $totalBeneficiaries = Beneficiary::count();
        $openIssues = self::where('status', DataQualityIssueStatus::Open)->count();

        $score = round(100 * (1 - $openIssues / max($totalBeneficiaries, 1)), 1);

        return [
            'score' => max(0.0, min(100.0, $score)),
            'total_beneficiaries' => $totalBeneficiaries,
            'open_issues' => $openIssues,
        ];
    }
}
