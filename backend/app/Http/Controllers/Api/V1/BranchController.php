<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\Organization;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyBranches->value);

        return ApiResponse::data(BranchResource::collection(Branch::orderBy('name')->get()));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageBranches->value);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'upazila' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $branch = Branch::create([
            ...$data,
            'organization_id' => Organization::current()->id,
        ]);

        return ApiResponse::data(new BranchResource($branch), status: 201);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageBranches->value);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'upazila' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $branch->update($data);

        return ApiResponse::data(new BranchResource($branch));
    }
}
