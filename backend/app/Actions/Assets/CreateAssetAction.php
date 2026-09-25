<?php

namespace App\Actions\Assets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;

class CreateAssetAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(array $attributes): Asset
    {
        // See CreateProgramAction (Phase 3) for why status is explicit, not a DB default.
        $asset = Asset::create([
            'status' => AssetStatus::Available,
            ...$attributes,
            'created_by' => Auth::id(),
        ]);

        $this->auditLogger->logCreated($asset, 'asset.created');

        return $asset;
    }
}
