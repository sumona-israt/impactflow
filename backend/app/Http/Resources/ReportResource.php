<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Report */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'format' => $this->format,
            'parameters' => $this->parameters,
            'generator' => $this->whenLoaded('generator', fn () => $this->generator ? [
                'id' => $this->generator->id,
                'name' => $this->generator->name,
            ] : null),
            'generated_at' => $this->generated_at,
        ];
    }
}
