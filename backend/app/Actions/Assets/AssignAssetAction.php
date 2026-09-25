<?php

namespace App\Actions\Assets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Validation\ValidationException;

class AssignAssetAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Asset $asset, User $assignee): AssetAssignment
    {
        if ($asset->status !== AssetStatus::Available) {
            throw ValidationException::withMessages([
                'status' => "This asset is currently {$asset->status->value} and cannot be assigned.",
            ]);
        }

        $assignment = AssetAssignment::create([
            'asset_id' => $asset->id,
            'assigned_to' => $assignee->id,
            'assigned_at' => now(),
        ]);

        $asset->update(['status' => AssetStatus::Assigned]);

        $this->auditLogger->log('asset.assigned', Asset::class, $asset->id, [], [
            'assigned_to' => $assignee->id,
            'assigned_to_name' => $assignee->name,
        ]);

        return $assignment;
    }
}
