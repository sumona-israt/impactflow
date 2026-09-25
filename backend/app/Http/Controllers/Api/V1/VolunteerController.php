<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Volunteers\CreateVolunteerAction;
use App\Actions\Volunteers\UpdateVolunteerAction;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Volunteers\StoreVolunteerRequest;
use App\Http\Requests\Volunteers\UpdateVolunteerRequest;
use App\Http\Resources\VolunteerResource;
use App\Models\Volunteer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VolunteerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyVolunteers->value);

        $perPage = min((int) $request->integer('per_page', 25), 100);

        $volunteers = Volunteer::query()
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->where('full_name', 'ilike', '%'.$request->string('q')->trim().'%');
            })
            ->when($request->filled('filter.status'), fn ($query) => $query->where('status', $request->input('filter.status')))
            ->orderBy('full_name')
            ->paginate($perPage);

        return ApiResponse::data(
            VolunteerResource::collection($volunteers->items()),
            ['page' => $volunteers->currentPage(), 'per_page' => $volunteers->perPage(), 'total' => $volunteers->total()],
        );
    }

    public function store(StoreVolunteerRequest $request, CreateVolunteerAction $action): JsonResponse
    {
        $volunteer = $action->execute($request->validated());

        return ApiResponse::data(new VolunteerResource($volunteer), status: 201);
    }

    public function update(UpdateVolunteerRequest $request, Volunteer $volunteer, UpdateVolunteerAction $action): JsonResponse
    {
        $volunteer = $action->execute($volunteer, $request->validated());

        return ApiResponse::data(new VolunteerResource($volunteer));
    }
}
