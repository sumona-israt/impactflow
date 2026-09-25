<?php

namespace App\Actions\Roles;

use App\Services\Audit\AuditLogger;
use Spatie\Permission\Models\Role;

class SyncRolePermissionsAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<int, string>  $permissions  permission names (App\Enums\PermissionEnum values)
     */
    public function execute(Role $role, array $permissions): Role
    {
        $before = $role->permissions()->pluck('name')->all();

        $role->syncPermissions($permissions);

        $after = $role->permissions()->pluck('name')->all();

        if ($before !== $after) {
            $this->auditLogger->log(
                'role.permissions_changed',
                Role::class,
                $role->id,
                ['permissions' => $before],
                ['permissions' => $after],
            );
        }

        return $role->fresh();
    }
}
