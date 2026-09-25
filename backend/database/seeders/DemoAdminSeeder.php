<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the single demo Super Administrator account documented in the root
 * README under "Demo Credentials". Password is env-configurable
 * (DEMO_ADMIN_PASSWORD) so it is never a hardcoded production secret; the
 * fallback is for local/demo use only.
 */
class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@impactflow.test'],
            [
                'name' => 'ImpactFlow Admin',
                'password' => env('DEMO_ADMIN_PASSWORD', 'password'),
            ]
        );

        $user->assignRole(RoleEnum::SuperAdmin->value);
    }
}
