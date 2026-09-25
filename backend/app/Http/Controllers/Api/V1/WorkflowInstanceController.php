<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workflow\RecordWorkflowActionAction;
use App\Enums\WorkflowDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\RecordWorkflowActionRequest;
use App\Http\Resources\WorkflowInstanceResource;
use App\Models\WorkflowInstance;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WorkflowInstanceController extends Controller
{
    public function act(RecordWorkflowActionRequest $request, WorkflowInstance $instance, RecordWorkflowActionAction $action): JsonResponse
    {
        $instance = $action->execute(
            $instance,
            Auth::user(),
            WorkflowDecision::from($request->validated('action')),
            $request->validated('comment'),
        );

        return ApiResponse::data(new WorkflowInstanceResource($instance->load(['currentStep', 'actions.step', 'actions.actor'])));
    }
}
