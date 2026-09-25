<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\Audit\AuditLogger;

class UpdateUserAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Profile fields only — activation state has its own action
     * (ToggleUserActiveAction) so the audit trail records "deactivated"
     * rather than a generic "updated" diff for that specific, sensitive change.
     */
    public function execute(User $user, array $attributes): User
    {
        $user->update($attributes);

        if ($user->wasChanged()) {
            $this->auditLogger->logUpdated($user, 'user.updated');
        }

        return $user;
    }
}
