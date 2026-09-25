<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

test('the database seeder creates all roles and a demo super admin', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (RoleEnum::cases() as $role) {
        expect(Role::where('name', $role->value)->exists())->toBeTrue();
    }

    $admin = User::where('email', 'admin@impactflow.test')->firstOrFail();

    expect($admin->hasRole(RoleEnum::SuperAdmin->value))->toBeTrue();
});
