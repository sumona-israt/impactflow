<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Assets\AssignAssetAction;
use App\Actions\Assets\CreateAssetAction;
use App\Actions\Assets\ReturnAssetAction;
use App\Actions\Assets\UpdateAssetAction;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\AssignAssetRequest;
use App\Http\Requests\Assets\StoreAssetRequest;
use App\Http\Requests\Assets\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyAssets->value);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $assets = Asset::query()
            ->when($request->string('q')->trim()->isNotEmpty(), function ($q) use ($request) {
                $q->where('name', 'ilike', '%'.$request->string('q')->trim().'%');
            })
            ->when($request->filled('filter.status'), fn ($q) => $q->where('status', $request->input('filter.status')))
            ->orderBy('name')
            ->paginate($perPage);

        return ApiResponse::data(
            AssetResource::collection($assets->items()),
            ['page' => $assets->currentPage(), 'per_page' => $assets->perPage(), 'total' => $assets->total()],
        );
    }

    public function store(StoreAssetRequest $request, CreateAssetAction $action): JsonResponse
    {
        $asset = $action->execute($request->validated());

        return ApiResponse::data(new AssetResource($asset), status: 201);
    }

    public function update(UpdateAssetRequest $request, Asset $asset, UpdateAssetAction $action): JsonResponse
    {
        $asset = $action->execute($asset, $request->validated());

        return ApiResponse::data(new AssetResource($asset));
    }

    public function assign(AssignAssetRequest $request, Asset $asset, AssignAssetAction $action): JsonResponse
    {
        $assignee = User::findOrFail($request->validated('user_id'));

        $action->execute($asset, $assignee);

        return ApiResponse::data(new AssetResource($asset->fresh()));
    }

    public function returnAsset(Asset $asset, ReturnAssetAction $action): JsonResponse
    {
        Gate::authorize(PermissionEnum::AssignAsset->value);

        $asset = $action->execute($asset);

        return ApiResponse::data(new AssetResource($asset));
    }
}
