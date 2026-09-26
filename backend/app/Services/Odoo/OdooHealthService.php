<?php

namespace App\Services\Odoo;

use App\Models\OdooConnection;
use App\Models\OdooSyncLog;
use Throwable;

/**
 * Backs GET /api/v1/odoo/status (see docs/odoo-integration.md §2).
 */
class OdooHealthService
{
    public function __construct(private readonly OdooAuthService $auth) {}

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $connection = OdooConnection::firstOrCreate(['name' => 'primary'], ['is_active' => true]);

        return [
            'mode' => config('odoo.mode'),
            'is_active' => $connection->is_active,
            'connected' => $this->probeConnectivity(),
            'entities' => $this->entityCounts(),
        ];
    }

    private function probeConnectivity(): bool
    {
        if (config('odoo.mode') !== 'live') {
            return true;
        }

        try {
            $this->auth->uid();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, array{synced: int, failed: int, last_success_at: ?string}>
     */
    private function entityCounts(): array
    {
        $counts = [];

        foreach (config('odoo.mappings') as $entityType => $mapping) {
            $base = OdooSyncLog::where('entity_type', $entityType);

            $counts[$mapping['slug']] = [
                'synced' => (clone $base)->where('status', 'success')->count(),
                'failed' => (clone $base)->where('status', 'failed')->count(),
                'last_success_at' => (clone $base)->where('status', 'success')->max('response_time'),
            ];
        }

        return $counts;
    }
}
