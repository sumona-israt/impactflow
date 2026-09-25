<?php

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Services\Audit\AuditLogger;

class UpdateAssetAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Asset $asset, array $attributes): Asset
    {
        $asset->update($attributes);

        if ($asset->wasChanged()) {
            $this->auditLogger->logUpdated($asset, 'asset.updated');
        }

        return $asset;
    }
}
