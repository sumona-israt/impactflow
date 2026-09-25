<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ViewAnyRoles->value);
    }

    public function managePermissions(User $user): bool
    {
        return $user->can(PermissionEnum::ManageRolePermissions->value);
    }
}
