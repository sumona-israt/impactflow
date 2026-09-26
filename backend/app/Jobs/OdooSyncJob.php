<?php

namespace App\Jobs;

use App\Models\OdooSyncLog;
use App\Services\Odoo\OdooSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Entity-agnostic — pushes one (entity_type, local_id) pair to Odoo via
 * OdooSyncService. See docs/odoo-integration.md §7 for the retry/failure
 * strategy this implements.
 */
class OdooSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public string $entityType,
        public string $localId,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 300, 900];
    }

    public function handle(OdooSyncService $service): void
    {
        $service->sync($this->entityType, $this->localId);
    }

    public function failed(?Throwable $exception): void
    {
        OdooSyncLog::where('entity_type', $this->entityType)
            ->where('local_id', $this->localId)
            ->where('operation', 'sync')
            ->update([
                'status' => 'failed',
                'error_message' => $exception?->getMessage(),
                'response_time' => now(),
            ]);
    }
}
