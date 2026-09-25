<?php

namespace App\Http\Resources;

use App\Models\Volunteer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Volunteer */
class VolunteerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'skills' => $this->skills ?? [],
            'availability' => $this->availability,
            'status' => $this->status,
        ];
    }
}
