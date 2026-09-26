<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\DataImports\CommitDataImportAction;
use App\Actions\DataImports\PreviewDataImportAction;
use App\Actions\DataImports\UpdateImportMappingAction;
use App\Actions\DataImports\UploadDataImportAction;
use App\Enums\DataImportStatus;
use App\Enums\ImportEntityType;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataImports\StoreDataImportRequest;
use App\Http\Requests\DataImports\UpdateImportMappingRequest;
use App\Http\Resources\DataImportResource;
use App\Http\Resources\DataImportRowResource;
use App\Models\DataImport;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DataImportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyDataImports->value);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $imports = DataImport::query()
            ->with('uploader')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ApiResponse::data(
            DataImportResource::collection($imports->items()),
            ['page' => $imports->currentPage(), 'per_page' => $imports->perPage(), 'total' => $imports->total()],
        );
    }

    public function store(StoreDataImportRequest $request, UploadDataImportAction $action): JsonResponse
    {
        $import = $action->execute(
            $request->file('file'),
            ImportEntityType::from($request->validated('entity_type')),
        );

        return ApiResponse::data(new DataImportResource($import->load('uploader')), status: 201);
    }

    public function show(DataImport $dataImport): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyDataImports->value);

        return ApiResponse::data(new DataImportResource($dataImport->load('uploader')));
    }

    public function updateMapping(UpdateImportMappingRequest $request, DataImport $dataImport, UpdateImportMappingAction $action): JsonResponse
    {
        $import = $action->execute($dataImport, $request->validated('mapping'));

        return ApiResponse::data(new DataImportResource($import->load('uploader')));
    }

    public function preview(DataImport $dataImport, PreviewDataImportAction $action): JsonResponse
    {
        Gate::authorize(PermissionEnum::CreateDataImport->value);

        if (! in_array($dataImport->status, [DataImportStatus::Mapped, DataImportStatus::Previewed], true)) {
            throw ValidationException::withMessages([
                'status' => 'Columns must be mapped before a preview can be generated.',
            ]);
        }

        $import = $action->execute($dataImport);

        return ApiResponse::data(new DataImportResource($import->load('uploader')));
    }

    public function showPreview(Request $request, DataImport $dataImport): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyDataImports->value);

        $perPage = min((int) $request->integer('per_page', 50), 200);

        $rows = $dataImport->rows()
            ->when($request->filled('filter.status'), fn ($q) => $q->where('status', $request->input('filter.status')))
            ->orderBy('row_number')
            ->paginate($perPage);

        return ApiResponse::data(
            DataImportRowResource::collection($rows->items()),
            ['page' => $rows->currentPage(), 'per_page' => $rows->perPage(), 'total' => $rows->total()],
        );
    }

    public function commit(DataImport $dataImport, CommitDataImportAction $action): JsonResponse
    {
        Gate::authorize(PermissionEnum::CreateDataImport->value);

        if ($dataImport->status !== DataImportStatus::Previewed) {
            throw ValidationException::withMessages([
                'status' => 'The import must be previewed before it can be committed.',
            ]);
        }

        $import = $action->execute($dataImport);

        return ApiResponse::data(new DataImportResource($import->load('uploader')));
    }
}
