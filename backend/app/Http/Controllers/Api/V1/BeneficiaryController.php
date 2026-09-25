<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Beneficiaries\CreateBeneficiaryAction;
use App\Actions\Beneficiaries\UpdateBeneficiaryAction;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Beneficiaries\StoreBeneficiaryRequest;
use App\Http\Requests\Beneficiaries\UpdateBeneficiaryRequest;
use App\Http\Resources\BeneficiaryListResource;
use App\Http\Resources\BeneficiaryResource;
use App\Http\Resources\EnrollmentResource;
use App\Models\Beneficiary;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BeneficiaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyBeneficiaries->value);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $beneficiaries = Beneficiary::query()
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->where('full_name', 'ilike', '%'.$request->string('q')->trim().'%');
            })
            ->when($request->filled('filter.status'), fn ($query) => $query->where('status', $request->input('filter.status')))
            ->when($request->filled('filter.district'), fn ($query) => $query->where('district', $request->input('filter.district')))
            ->orderByDesc('registration_date')
            ->paginate($perPage);

        return ApiResponse::data(
            BeneficiaryListResource::collection($beneficiaries->items()),
            ['page' => $beneficiaries->currentPage(), 'per_page' => $beneficiaries->perPage(), 'total' => $beneficiaries->total()],
        );
    }

    public function store(StoreBeneficiaryRequest $request, CreateBeneficiaryAction $action): JsonResponse
    {
        $beneficiary = $action->execute($request->validated());

        return ApiResponse::data(new BeneficiaryResource($beneficiary), status: 201);
    }

    public function show(Beneficiary $beneficiary): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewBeneficiary->value);

        return ApiResponse::data(new BeneficiaryResource($beneficiary));
    }

    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary, UpdateBeneficiaryAction $action): JsonResponse
    {
        $beneficiary = $action->execute($beneficiary, $request->validated());

        return ApiResponse::data(new BeneficiaryResource($beneficiary));
    }

    public function enrollments(Beneficiary $beneficiary): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewBeneficiary->value);

        $enrollments = $beneficiary->beneficiaryPrograms()->with('program')->latest('enrolled_at')->get();

        return ApiResponse::data(EnrollmentResource::collection($enrollments));
    }
}
