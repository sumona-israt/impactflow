<?php

namespace App\Http\Resources;

use App\Models\DataImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DataImport */
class DataImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_type' => $this->entity_type,
            'original_name' => $this->original_name,
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
            ]),
            'status' => $this->status,
            'detected_headers' => $this->detected_headers,
            'column_mapping' => $this->column_mapping,
            'total_rows' => $this->total_rows,
            'valid_rows' => $this->valid_rows,
            'duplicate_rows' => $this->duplicate_rows,
            'invalid_rows' => $this->invalid_rows,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at,
        ];
    }
}
