<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ViewAnyUsers->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermissionEnum::ViewUser->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CreateUser->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermissionEnum::UpdateUser->value);
    }

    public function manageRoles(User $user): bool
    {
        return $user->can(PermissionEnum::ManageUserRoles->value);
    }
}
