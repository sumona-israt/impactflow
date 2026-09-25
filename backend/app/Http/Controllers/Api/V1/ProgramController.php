<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Programs\CreateProgramAction;
use App\Actions\Programs\EnrollBeneficiaryAction;
use App\Actions\Programs\UnenrollBeneficiaryAction;
use App\Actions\Programs\UpdateProgramAction;
use App\Actions\Programs\UpdateProgramStatusAction;
use App\Enums\PermissionEnum;
use App\Enums\ProgramStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Programs\EnrollBeneficiaryRequest;
use App\Http\Requests\Programs\StoreProgramRequest;
use App\Http\Requests\Programs\UpdateProgramRequest;
use App\Http\Requests\Programs\UpdateProgramStatusRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\ProgramResource;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProgramController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyPrograms->value);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $programs = Program::query()
            ->with(['category', 'manager', 'branch'])
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->where('name', 'ilike', '%'.$request->string('q')->trim().'%');
            })
            ->when($request->filled('filter.status'), fn ($query) => $query->where('status', $request->input('filter.status')))
            ->when($request->filled('filter.category_id'), fn ($query) => $query->where('category_id', $request->input('filter.category_id')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ApiResponse::data(
            ProgramResource::collection($programs->items()),
            ['page' => $programs->currentPage(), 'per_page' => $programs->perPage(), 'total' => $programs->total()],
        );
    }

    public function store(StoreProgramRequest $request, CreateProgramAction $action): JsonResponse
    {
        $program = $action->execute($request->validated());

        return ApiResponse::data(new ProgramResource($program->load(['category', 'manager', 'branch'])), status: 201);
    }

    public function show(Program $program): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewProgram->value);

        return ApiResponse::data(new ProgramResource($program->load(['category', 'manager', 'branch'])));
    }

    public function update(UpdateProgramRequest $request, Program $program, UpdateProgramAction $action): JsonResponse
    {
        $program = $action->execute($program, $request->validated());

        return ApiResponse::data(new ProgramResource($program->load(['category', 'manager', 'branch'])));
    }

    public function updateStatus(UpdateProgramStatusRequest $request, Program $program, UpdateProgramStatusAction $action): JsonResponse
    {
        $program = $action->execute($program, ProgramStatus::from($request->validated('status')));

        return ApiResponse::data(new ProgramResource($program));
    }

    public function beneficiaries(Program $program): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewProgram->value);

        $enrollments = $program->beneficiaryPrograms()->with('beneficiary')->latest('enrolled_at')->get();

        return ApiResponse::data(EnrollmentResource::collection($enrollments));
    }

    public function enroll(EnrollBeneficiaryRequest $request, Program $program, EnrollBeneficiaryAction $action): JsonResponse
    {
        $beneficiary = Beneficiary::findOrFail($request->validated('beneficiary_id'));

        $enrollment = $action->execute($program, $beneficiary);

        return ApiResponse::data(new EnrollmentResource($enrollment->load('beneficiary')), status: 201);
    }

    public function unenroll(Program $program, BeneficiaryProgram $enrollment, UnenrollBeneficiaryAction $action): JsonResponse
    {
        Gate::authorize(PermissionEnum::EnrollBeneficiaryInProgram->value);

        abort_if($enrollment->program_id !== $program->id, 404);

        $enrollment = $action->execute($enrollment);

        return ApiResponse::data(new EnrollmentResource($enrollment));
    }

    public function activities(Program $program): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewProgram->value);

        $activities = $program->activities()->withCount('attendanceRecords')->orderByDesc('scheduled_at')->get();

        return ApiResponse::data(ActivityResource::collection($activities));
    }
}
