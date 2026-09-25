<?php

namespace App\Actions\Assets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Services\Audit\AuditLogger;
use Illuminate\Validation\ValidationException;

class ReturnAssetAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Asset $asset): Asset
    {
        $assignment = $asset->currentAssignment();

        if (! $assignment) {
            throw ValidationException::withMessages([
                'status' => 'This asset is not currently assigned to anyone.',
            ]);
        }

        $assignment->update(['returned_at' => now()]);
        $asset->update(['status' => AssetStatus::Available]);

        $this->auditLogger->log('asset.returned', Asset::class, $asset->id, [], [
            'assignment_id' => $assignment->id,
        ]);

        return $asset->fresh();
    }
}
