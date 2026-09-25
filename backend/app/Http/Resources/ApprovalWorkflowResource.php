<?php

namespace App\Http\Resources;

use App\Models\ApprovalWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApprovalWorkflow */
class ApprovalWorkflowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'entity_type' => class_basename($this->entity_type),
            'steps' => $this->whenLoaded('steps', fn () => $this->steps->map(fn ($step) => [
                'sequence' => $step->sequence,
                'name' => $step->name,
                'role_required' => $step->role_required,
            ])),
        ];
    }
}
