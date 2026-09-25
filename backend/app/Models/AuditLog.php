<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable audit trail row (see docs/database-design.md §1). Written only
 * by App\Services\Audit\AuditLogger — never updated or deleted by the app.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
