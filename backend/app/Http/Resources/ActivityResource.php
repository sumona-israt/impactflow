<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'title' => $this->title,
            'description' => $this->description,
            'scheduled_at' => $this->scheduled_at,
            'location' => $this->location,
            'status' => $this->status,
            'attendance_count' => $this->whenCounted('attendanceRecords'),
        ];
    }
}
