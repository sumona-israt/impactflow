<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\Audit\AuditLogger;

class ToggleUserActiveAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(User $user, bool $active): User
    {
        $wasActive = $user->is_active;

        $user->update(['is_active' => $active]);

        if ($wasActive !== $active) {
            $this->auditLogger->log(
                $active ? 'user.activated' : 'user.deactivated',
                User::class,
                $user->id,
                ['is_active' => $wasActive],
                ['is_active' => $active],
            );
        }

        return $user;
    }
}
