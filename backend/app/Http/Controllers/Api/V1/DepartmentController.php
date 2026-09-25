<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\Organization;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyDepartments->value);

        return ApiResponse::data(DepartmentResource::collection(Department::orderBy('name')->get()));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageDepartments->value);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $department = Department::create([
            ...$data,
            'organization_id' => Organization::current()->id,
        ]);

        return ApiResponse::data(new DepartmentResource($department), status: 201);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        Gate::authorize(PermissionEnum::ManageDepartments->value);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'parent_department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $department->update($data);

        return ApiResponse::data(new DepartmentResource($department));
    }
}
