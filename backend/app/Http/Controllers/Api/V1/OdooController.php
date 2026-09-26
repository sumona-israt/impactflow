<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\OdooSyncLogResource;
use App\Jobs\OdooSyncJob;
use App\Models\OdooConnection;
use App\Models\OdooSyncLog;
use App\Services\Odoo\OdooHealthService;
use App\Services\Odoo\OdooMappingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OdooController extends Controller
{
    public function status(OdooHealthService $health): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewOdooStatus->value);

        return ApiResponse::data($health->status());
    }

    public function syncLogs(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewOdooStatus->value);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $logs = OdooSyncLog::query()
            ->when($request->filled('filter.status'), fn ($q) => $q->where('status', $request->input('filter.status')))
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return ApiResponse::data(
            OdooSyncLogResource::collection($logs->items()),
            ['page' => $logs->currentPage(), 'per_page' => $logs->perPage(), 'total' => $logs->total()],
        );
    }

    public function retry(string $entity, string $id, OdooMappingService $mapping): JsonResponse
    {
        Gate::authorize(PermissionEnum::RetryOdooSync->value);

        $entityType = $mapping->entityTypeForSlug($entity);

        OdooSyncJob::dispatch($entityType, $id);

        return ApiResponse::message('Sync retry queued.');
    }

    public function config(): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewOdooStatus->value);

        $connection = OdooConnection::firstOrCreate(['name' => 'primary'], ['is_active' => true]);

        return ApiResponse::data([
            'mode' => config('odoo.mode'),
            'base_url' => config('odoo.base_url'),
            'database' => config('odoo.database'),
            'is_active' => $connection->is_active,
        ]);
    }

    public function updateConfig(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageOdooConfig->value);

        $validated = $request->validate(['is_active' => 'required|boolean']);

        $connection = OdooConnection::firstOrCreate(['name' => 'primary'], ['is_active' => true]);
        $connection->update($validated);

        return ApiResponse::data([
            'mode' => config('odoo.mode'),
            'base_url' => config('odoo.base_url'),
            'database' => config('odoo.database'),
            'is_active' => $connection->is_active,
        ]);
    }
}
