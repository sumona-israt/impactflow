<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(RoleEnum::SuperAdmin->value);

    $this->plainUser = User::factory()->create();
    $this->plainUser->assignRole(RoleEnum::FieldOfficer->value);
});

test('a super admin can list roles with their permissions', function () {
    $this->actingAs($this->superAdmin)
        ->getJson('/api/v1/roles')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'permissions']]]);
});

test('a non-privileged user cannot list roles or permissions', function () {
    $this->actingAs($this->plainUser)->getJson('/api/v1/roles')->assertForbidden();
    $this->actingAs($this->plainUser)->getJson('/api/v1/permissions')->assertForbidden();
});

test('a super admin can grant a permission to a role and it is audited', function () {
    $role = Role::findByName(RoleEnum::ProgramManager->value, 'web');

    $this->actingAs($this->superAdmin)
        ->putJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => [PermissionEnum::ViewAnyAuditLogs->value],
        ])
        ->assertOk()
        ->assertJsonPath('data.permissions.0', PermissionEnum::ViewAnyAuditLogs->value);

    expect(AuditLog::where('action', 'role.permissions_changed')->where('entity_id', $role->id)->exists())->toBeTrue();
});

test('an unknown permission name is rejected', function () {
    $role = Role::findByName(RoleEnum::ProgramManager->value, 'web');

    $this->actingAs($this->superAdmin)
        ->putJson("/api/v1/roles/{$role->id}/permissions", ['permissions' => ['not-a-real-permission']])
        ->assertUnprocessable();
});
