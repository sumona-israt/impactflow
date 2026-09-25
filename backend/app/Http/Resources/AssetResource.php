<?php

namespace App\Http\Resources;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Asset */
class AssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentAssignment = $this->currentAssignment();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'serial_number' => $this->serial_number,
            'purchase_date' => $this->purchase_date,
            'purchase_value' => $this->purchase_value,
            'location' => $this->location,
            'condition' => $this->condition,
            'status' => $this->status,
            'assigned_to' => $currentAssignment ? [
                'id' => $currentAssignment->assignee->id,
                'name' => $currentAssignment->assignee->name,
                'assigned_at' => $currentAssignment->assigned_at,
            ] : null,
        ];
    }
}
