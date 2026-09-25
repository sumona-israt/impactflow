<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Employees\CreateEmployeeAction;
use App\Actions\Employees\UpdateEmployeeAction;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyEmployees->value);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $employees = Employee::query()
            ->with(['department', 'branch'])
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->where('name', 'ilike', '%'.$request->string('q')->trim().'%');
            })
            ->when($request->filled('filter.status'), fn ($query) => $query->where('status', $request->input('filter.status')))
            ->orderBy('name')
            ->paginate($perPage);

        return ApiResponse::data(
            EmployeeResource::collection($employees->items()),
            ['page' => $employees->currentPage(), 'per_page' => $employees->perPage(), 'total' => $employees->total()],
        );
    }

    public function store(StoreEmployeeRequest $request, CreateEmployeeAction $action): JsonResponse
    {
        $employee = $action->execute($request->validated());

        return ApiResponse::data(new EmployeeResource($employee->load(['department', 'branch'])), status: 201);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, UpdateEmployeeAction $action): JsonResponse
    {
        $employee = $action->execute($employee, $request->validated());

        return ApiResponse::data(new EmployeeResource($employee->load(['department', 'branch'])));
    }
}
