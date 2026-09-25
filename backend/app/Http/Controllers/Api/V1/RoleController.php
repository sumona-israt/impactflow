<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Roles\SyncRolePermissionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\SyncRolePermissionsRequest;
use App\Http\Resources\RoleResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::with('permissions')->orderBy('name')->get();

        return ApiResponse::data(RoleResource::collection($roles));
    }

    public function updatePermissions(SyncRolePermissionsRequest $request, Role $role, SyncRolePermissionsAction $action): JsonResponse
    {
        $role = $action->execute($role, $request->validated('permissions'));

        return ApiResponse::data(new RoleResource($role));
    }
}
