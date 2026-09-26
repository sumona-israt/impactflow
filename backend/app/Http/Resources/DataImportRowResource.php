<?php

namespace App\Http\Resources;

use App\Models\DataImportRow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DataImportRow */
class DataImportRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_number' => $this->row_number,
            'raw_data' => $this->raw_data,
            'status' => $this->status,
            'errors' => $this->errors,
            'beneficiary_id' => $this->beneficiary_id,
        ];
    }
}
