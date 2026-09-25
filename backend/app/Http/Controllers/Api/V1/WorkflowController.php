<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApprovalWorkflowResource;
use App\Models\ApprovalWorkflow;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WorkflowController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize(PermissionEnum::ViewAnyWorkflows->value);

        return ApiResponse::data(ApprovalWorkflowResource::collection(ApprovalWorkflow::with('steps')->get()));
    }
}
