<?php

namespace App\Http\Resources;

use App\Models\OdooSyncLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OdooSyncLog */
class OdooSyncLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_type' => class_basename($this->entity_type),
            'local_id' => $this->local_id,
            'odoo_id' => $this->odoo_id,
            'operation' => $this->operation,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'retry_count' => $this->retry_count,
            'request_time' => $this->request_time,
            'response_time' => $this->response_time,
        ];
    }
}
