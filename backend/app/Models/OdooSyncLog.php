<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooSyncLog extends Model
{
    protected $fillable = [
        'entity_type', 'local_id', 'odoo_id', 'operation', 'status',
        'request_payload', 'response_payload', 'error_message', 'retry_count',
        'request_time', 'response_time',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'request_time' => 'datetime',
            'response_time' => 'datetime',
        ];
    }
}
