<?php

namespace App\Jobs;

use App\Enums\RoleEnum;
use App\Models\OdooSyncLog;
use App\Models\User;
use App\Notifications\OdooSyncFailed;
use App\Services\Odoo\OdooSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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
        $errorMessage = $exception?->getMessage() ?? 'Unknown error.';

        OdooSyncLog::where('entity_type', $this->entityType)
            ->where('local_id', $this->localId)
            ->where('operation', 'sync')
            ->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'response_time' => now(),
            ]);

        // Management is the oversight role for the Odoo dashboard (see
        // docs/database-design.md §10) — no separate event/listener for this
        // single, already-final hook (see docs/database-design.md §11). A
        // notification-delivery problem must never mask the log update above,
        // which already recorded the real failure.
        try {
            Notification::send(
                User::role(RoleEnum::Management->value)->get(),
                new OdooSyncFailed($this->entityType, $this->localId, $errorMessage),
            );
        } catch (Throwable $notifyException) {
            Log::error('Failed to notify Management of an exhausted Odoo sync.', [
                'entity_type' => $this->entityType,
                'local_id' => $this->localId,
                'exception' => $notifyException->getMessage(),
            ]);
        }
    }
}
