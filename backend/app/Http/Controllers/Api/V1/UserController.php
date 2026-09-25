<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\SyncUserRolesAction;
use App\Actions\Users\ToggleUserActiveAction;
use App\Actions\Users\UpdateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\SyncUserRolesRequest;
use App\Http\Requests\Users\ToggleUserActiveRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $users = User::query()
            ->with('roles')
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = '%'.$request->string('q')->trim().'%';
                $query->where(fn ($q) => $q->where('name', 'ilike', $search)->orWhere('email', 'ilike', $search));
            })
            ->when($request->filled('filter.role'), fn ($query) => $query->role($request->input('filter.role')))
            ->when($request->filled('filter.is_active'), fn ($query) => $query->where('is_active', $request->boolean('filter.is_active')))
            ->orderBy('name')
            ->paginate($perPage);

        return ApiResponse::data(
            UserResource::collection($users->items()),
            [
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        );
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
    {
        $user = $action->execute($request->safe()->except('roles'), $request->validated('roles'));

        return ApiResponse::data(new UserResource($user), status: 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::data(new UserResource($user->load('roles')));
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): JsonResponse
    {
        $user = $action->execute($user, $request->validated());

        return ApiResponse::data(new UserResource($user));
    }

    public function syncRoles(SyncUserRolesRequest $request, User $user, SyncUserRolesAction $action): JsonResponse
    {
        $user = $action->execute($user, $request->validated('roles'));

        return ApiResponse::data(new UserResource($user));
    }

    public function toggleActive(ToggleUserActiveRequest $request, User $user, ToggleUserActiveAction $action): JsonResponse
    {
        if ($user->is($request->user()) && ! $request->boolean('is_active')) {
            return ApiResponse::message('You cannot deactivate your own account.', 422);
        }

        $user = $action->execute($user, $request->boolean('is_active'));

        return ApiResponse::data(new UserResource($user));
    }
}
