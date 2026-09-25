<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('filter.user_id'), fn ($q) => $q->where('user_id', $request->input('filter.user_id')))
            ->when($request->filled('filter.action'), fn ($q) => $q->where('action', $request->input('filter.action')))
            ->when($request->filled('filter.entity_type'), fn ($q) => $q->where('entity_type', 'like', '%'.$request->input('filter.entity_type').'%'))
            ->when($request->filled('filter.date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('filter.date_from')))
            ->when($request->filled('filter.date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('filter.date_to')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ApiResponse::data(
            AuditLogResource::collection($logs->items()),
            [
                'page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        );
    }
}
