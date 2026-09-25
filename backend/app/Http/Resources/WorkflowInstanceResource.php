<?php

namespace App\Http\Resources;

use App\Models\WorkflowInstance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowInstance */
class WorkflowInstanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            // role_required travels with the step so any caller who can see
            // this instance (i.e. anyone with expenses.view) can tell
            // whether *they* are the one who can act next, without needing
            // separate workflows.viewAny access to the definition itself.
            'current_step' => $this->whenLoaded('currentStep', fn () => $this->currentStep ? [
                'name' => $this->currentStep->name,
                'role_required' => $this->currentStep->role_required,
            ] : null),
            'actions' => WorkflowActionResource::collection($this->whenLoaded('actions')),
        ];
    }
}
