<?php

namespace App\Services\Odoo;

use App\Contracts\OdooClientInterface;
use App\Exceptions\OdooConnectionException;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\OdooConnection;
use App\Models\OdooMapping;
use App\Models\OdooSyncLog;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Orchestrates one sync operation for one entity (see
 * docs/odoo-integration.md §2): build the payload, call the client, write an
 * odoo_sync_logs row, update odoo_mappings. Entity-agnostic — knows nothing
 * about Program/Expense/etc. beyond what OdooMappingService's config tells it.
 */
class OdooSyncService
{
    public function __construct(
        private readonly OdooClientInterface $client,
        private readonly OdooMappingService $mapping,
    ) {}

    public function sync(string $entityType, string $localId): void
    {
        $entity = $entityType::findOrFail($localId);
        $model = $this->mapping->odooModelFor($entityType);
        $operation = 'sync';

        $log = OdooSyncLog::firstOrNew([
            'entity_type' => $entityType,
            'local_id' => $localId,
            'operation' => $operation,
        ]);
        $log->retry_count = ($log->retry_count ?? 0) + ($log->exists ? 1 : 0);

        $connection = OdooConnection::firstOrCreate(['name' => 'primary'], ['is_active' => true]);

        if (! $connection->is_active) {
            $log->fill(['status' => 'skipped', 'request_time' => now(), 'response_time' => now()])->save();

            return;
        }

        $existingMapping = OdooMapping::where('entity_type', $entityType)
            ->where('local_id', $localId)
            ->where('odoo_model', $model)
            ->first();

        $payload = $this->mapping->buildPayload($entity);

        $log->fill([
            'status' => 'pending',
            'request_payload' => $payload,
            'request_time' => now(),
        ])->save();

        try {
            if ($existingMapping) {
                $this->client->callKw($model, 'write', [[$existingMapping->odoo_id], $payload]);
                $odooId = $existingMapping->odoo_id;
            } else {
                $odooId = $this->client->callKw($model, 'create', [$payload]);
            }

            $log->fill([
                'status' => 'success',
                'odoo_id' => $odooId,
                'response_payload' => ['odoo_id' => $odooId],
                'response_time' => now(),
                'error_message' => null,
            ])->save();

            OdooMapping::updateOrCreate(
                ['entity_type' => $entityType, 'local_id' => $localId, 'odoo_model' => $model],
                ['odoo_id' => $odooId],
            );

            $this->updateDenormalizedColumn($entity, $odooId);
        } catch (Throwable $e) {
            $log->fill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'response_time' => now(),
            ])->save();

            throw $e instanceof OdooConnectionException ? $e : new OdooConnectionException($e->getMessage(), previous: $e);
        }
    }

    private function updateDenormalizedColumn(Model $entity, int $odooId): void
    {
        match ($entity::class) {
            Program::class => $entity->update(['odoo_project_id' => $odooId]),
            Employee::class => $entity->update(['odoo_employee_id' => $odooId]),
            Expense::class => $entity->update(['odoo_synced' => true]),
            default => null,
        };
    }
}
