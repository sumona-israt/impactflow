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

test('a super admin can list and filter audit logs', function () {
    AuditLog::create([
        'user_id' => $this->superAdmin->id,
        'action' => 'user.created',
        'entity_type' => User::class,
        'entity_id' => $this->plainUser->id,
        'old_values' => [],
        'new_values' => ['name' => $this->plainUser->name],
        'created_at' => now(),
    ]);
    AuditLog::create([
        'user_id' => $this->superAdmin->id,
        'action' => 'role.permissions_changed',
        'entity_type' => 'Spatie\\Permission\\Models\\Role',
        'entity_id' => 1,
        'old_values' => [],
        'new_values' => [],
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->getJson('/api/v1/audit-logs?filter[action]=user.created')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.action'))->toBe('user.created');
    expect($response->json('data.0.user.email'))->toBe($this->superAdmin->email);
});

test('a non-privileged user cannot view audit logs', function () {
    $this->actingAs($this->plainUser)
        ->getJson('/api/v1/audit-logs')
        ->assertForbidden();
});
