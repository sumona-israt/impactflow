<?php

namespace App\Http\Resources;

use App\Models\WorkflowAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowAction */
class WorkflowActionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
            'step' => $this->whenLoaded('step', fn () => $this->step->name),
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ]),
        ];
    }
}
