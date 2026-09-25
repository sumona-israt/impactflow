<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Runs after RoleSeeder. Only Super Administrator gets these permissions
     * today — user/role/audit-log administration is a Super Admin-only
     * capability per the product brief; other roles gain permissions here
     * only if a future phase actually grants them one.
     */
    public function run(): void
    {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        // syncPermissions() validates names against the registrar's cached
        // permission list, which findOrCreate() above doesn't invalidate.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findByName(RoleEnum::SuperAdmin->value, 'web')
            ->syncPermissions(array_map(fn (PermissionEnum $p) => $p->value, PermissionEnum::cases()));
    }
}
