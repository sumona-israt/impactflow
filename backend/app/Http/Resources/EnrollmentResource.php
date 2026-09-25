<?php

namespace App\Http\Resources;

use App\Models\BeneficiaryProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BeneficiaryProgram */
class EnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at,
            'beneficiary' => $this->whenLoaded('beneficiary', fn () => new BeneficiaryListResource($this->beneficiary)),
            'program' => $this->whenLoaded('program', fn () => [
                'id' => $this->program->id,
                'name' => $this->program->name,
            ]),
        ];
    }
}
