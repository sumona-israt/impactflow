<?php

use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(RoleEnum::SuperAdmin->value);

    $this->plainUser = User::factory()->create();
    $this->plainUser->assignRole(RoleEnum::FieldOfficer->value);
});

test('a super admin can list users', function () {
    User::factory()->count(3)->create();

    $this->actingAs($this->superAdmin)
        ->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total']]);
});

test('a non-privileged user cannot list users', function () {
    $this->actingAs($this->plainUser)
        ->getJson('/api/v1/users')
        ->assertForbidden();
});

test('a super admin can create a user with roles and it is audited', function () {
    $response = $this->actingAs($this->superAdmin)->postJson('/api/v1/users', [
        'name' => 'New Officer',
        'email' => 'new.officer@impactflow.test',
        'password' => 'super-secret-1',
        'roles' => [RoleEnum::ProgramManager->value],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'new.officer@impactflow.test')
        ->assertJsonPath('data.roles.0', RoleEnum::ProgramManager->value)
        ->assertJsonPath('data.is_active', true);

    $log = AuditLog::where('action', 'user.created')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->new_values['password'])->toBe('[REDACTED]');
});

test('creating a user requires a role and rejects unknown roles', function () {
    $this->actingAs($this->superAdmin)
        ->postJson('/api/v1/users', [
            'name' => 'No Role',
            'email' => 'no.role@impactflow.test',
            'password' => 'super-secret-1',
            'roles' => ['not-a-real-role'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('roles.0');
});

test('a super admin can update a user profile', function () {
    $target = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($this->superAdmin)
        ->putJson("/api/v1/users/{$target->id}", ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    expect(AuditLog::where('action', 'user.updated')->where('entity_id', $target->id)->exists())->toBeTrue();
});

test('a super admin can change a user\'s roles', function () {
    $target = User::factory()->create();
    $target->assignRole(RoleEnum::FieldOfficer->value);

    $this->actingAs($this->superAdmin)
        ->putJson("/api/v1/users/{$target->id}/roles", ['roles' => [RoleEnum::FinanceOfficer->value]])
        ->assertOk()
        ->assertJsonPath('data.roles.0', RoleEnum::FinanceOfficer->value);

    expect(AuditLog::where('action', 'user.roles_changed')->where('entity_id', $target->id)->exists())->toBeTrue();
});

test('a super admin can deactivate another user', function () {
    $target = User::factory()->create(['is_active' => true]);

    $this->actingAs($this->superAdmin)
        ->patchJson("/api/v1/users/{$target->id}/active", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect(AuditLog::where('action', 'user.deactivated')->where('entity_id', $target->id)->exists())->toBeTrue();
});

test('a super admin cannot deactivate their own account', function () {
    $this->actingAs($this->superAdmin)
        ->patchJson("/api/v1/users/{$this->superAdmin->id}/active", ['is_active' => false])
        ->assertStatus(422);

    expect($this->superAdmin->fresh()->is_active)->toBeTrue();
});
