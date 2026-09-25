<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<int, string>  $roles  role slugs (App\Enums\RoleEnum values)
     */
    public function execute(array $attributes, array $roles): User
    {
        $user = User::create([
            ...$attributes,
            'password' => Hash::make($attributes['password']),
            // Explicit rather than relying on the DB column default: Eloquent
            // doesn't reload defaults-only columns after create(), so the
            // in-memory model (and therefore the API response) would
            // otherwise show is_active as null instead of true.
            'is_active' => true,
        ]);

        $user->syncRoles($roles);

        $this->auditLogger->log('user.created', User::class, $user->id, [], [
            ...$user->getAttributes(),
            'roles' => $roles,
        ]);

        return $user;
    }
}
