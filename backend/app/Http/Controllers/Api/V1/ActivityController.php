<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Activities\CreateActivityAction;
use App\Actions\Activities\RecordAttendanceAction;
use App\Actions\Activities\UpdateActivityAction;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activities\RecordAttendanceRequest;
use App\Http\Requests\Activities\StoreActivityRequest;
use App\Http\Requests\Activities\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Program;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ActivityController extends Controller
{
    public function store(StoreActivityRequest $request, Program $program, CreateActivityAction $action): JsonResponse
    {
        $activity = $action->execute([...$request->validated(), 'program_id' => $program->id]);

        return ApiResponse::data(new ActivityResource($activity), status: 201);
    }

    public function show(Activity $activity): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyActivities->value);

        return ApiResponse::data(new ActivityResource($activity->loadCount('attendanceRecords')));
    }

    public function update(UpdateActivityRequest $request, Activity $activity, UpdateActivityAction $action): JsonResponse
    {
        $activity = $action->execute($activity, $request->validated());

        return ApiResponse::data(new ActivityResource($activity));
    }

    public function attendance(Activity $activity): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyActivities->value);

        $records = $activity->attendanceRecords()->with('beneficiary')->get();

        return ApiResponse::data($records->map(fn ($record) => [
            'beneficiary_id' => $record->beneficiary_id,
            'beneficiary_name' => $record->beneficiary->full_name,
            'attended' => $record->attended,
        ]));
    }

    public function recordAttendance(RecordAttendanceRequest $request, Activity $activity, RecordAttendanceAction $action): JsonResponse
    {
        $records = $action->execute($activity, $request->validated('attendance'));

        return ApiResponse::data($records->map(fn ($record) => [
            'beneficiary_id' => $record->beneficiary_id,
            'attended' => $record->attended,
        ]));
    }
}
