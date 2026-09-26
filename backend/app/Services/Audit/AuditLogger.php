<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * The one place that writes audit_logs rows (see docs/architecture.md).
 * Every sensitive mutation across every phase should go through this rather
 * than writing AuditLog rows directly, so redaction and actor/IP capture
 * stay consistent.
 */
class AuditLogger
{
    /**
     * Fields never written to the audit trail, even redacted-out, because
     * their presence alone (e.g. which fields changed) can leak information.
     * The value is replaced with a fixed marker instead of being dropped, so
     * "this field changed" is still visible without exposing its value.
     */
    private const REDACTED_FIELDS = [
        'password',
        'remember_token',
        // PII — see docs/database-design.md §1/§4 ("sensitive fields... redacted").
        // A flat, global list rather than per-model config, matching this
        // class's entity-agnostic design; redacting `phone` also covers
        // User.phone consistently, an acceptable trade-off for simplicity.
        'phone',
        'date_of_birth',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    public function log(string $action, string $entityType, string|int|null $entityId, array $old = [], array $new = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $this->redact($old),
            'new_values' => $this->redact($new),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    public function logCreated(Model $model, string $action): AuditLog
    {
        return $this->log($action, $model::class, $model->getKey(), [], $model->getAttributes());
    }

    public function logUpdated(Model $model, string $action): AuditLog
    {
        return $this->log(
            $action,
            $model::class,
            $model->getKey(),
            $model->getOriginal(),
            $model->getChanges(),
        );
    }

    private function redact(array $values): array
    {
        foreach (self::REDACTED_FIELDS as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = '[REDACTED]';
            }
        }

        return $values;
    }
}
