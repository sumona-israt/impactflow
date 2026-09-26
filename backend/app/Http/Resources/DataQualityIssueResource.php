<?php

namespace App\Http\Resources;

use App\Models\DataQualityIssue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DataQualityIssue */
class DataQualityIssueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_type' => class_basename($this->entity_type),
            'entity_id' => $this->entity_id,
            'entity' => $this->whenLoaded('entity', fn () => $this->entity ? [
                'id' => $this->entity->id,
                'name' => $this->entity->full_name ?? null,
            ] : null),
            'issue_type' => $this->issue_type,
            'severity' => $this->severity,
            'description' => $this->description,
            'status' => $this->status,
            'detected_at' => $this->detected_at,
            'resolved_at' => $this->resolved_at,
            'resolver' => $this->whenLoaded('resolver', fn () => $this->resolver ? [
                'id' => $this->resolver->id,
                'name' => $this->resolver->name,
            ] : null),
        ];
    }
}
