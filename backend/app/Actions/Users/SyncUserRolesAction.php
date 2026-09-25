<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\Audit\AuditLogger;

class SyncUserRolesAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<int, string>  $roles  role slugs (App\Enums\RoleEnum values)
     */
    public function execute(User $user, array $roles): User
    {
        $before = $user->getRoleNames()->all();

        $user->syncRoles($roles);

        $after = $user->getRoleNames()->all();

        if ($before !== $after) {
            $this->auditLogger->log(
                'user.roles_changed',
                User::class,
                $user->id,
                ['roles' => $before],
                ['roles' => $after],
            );
        }

        return $user->fresh();
    }
}
