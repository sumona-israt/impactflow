<?php

use App\Enums\RoleEnum;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->hrOfficer = User::factory()->create();
    $this->hrOfficer->assignRole(RoleEnum::HrAdminOfficer->value);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);
});

test('an hr officer can create an asset and it is audited', function () {
    $this->actingAs($this->hrOfficer)
        ->postJson('/api/v1/assets', ['name' => 'Toyota Hilux', 'category' => 'Vehicle'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'available');

    expect(AuditLog::where('action', 'asset.created')->exists())->toBeTrue();
});

test('a field officer cannot manage assets', function () {
    $this->actingAs($this->fieldOfficer)
        ->postJson('/api/v1/assets', ['name' => 'Should Fail'])
        ->assertForbidden();
});

test('an available asset can be assigned and then returned', function () {
    $asset = Asset::factory()->create();
    $assignee = User::factory()->create();

    $this->actingAs($this->hrOfficer)
        ->postJson("/api/v1/assets/{$asset->id}/assign", ['user_id' => $assignee->id])
        ->assertOk()
        ->assertJsonPath('data.status', 'assigned')
        ->assertJsonPath('data.assigned_to.id', $assignee->id);

    // Cannot assign an already-assigned asset
    $anotherUser = User::factory()->create();
    $this->actingAs($this->hrOfficer)
        ->postJson("/api/v1/assets/{$asset->id}/assign", ['user_id' => $anotherUser->id])
        ->assertUnprocessable();

    $this->actingAs($this->hrOfficer)
        ->postJson("/api/v1/assets/{$asset->id}/return")
        ->assertOk()
        ->assertJsonPath('data.status', 'available')
        ->assertJsonPath('data.assigned_to', null);

    expect(AuditLog::where('action', 'asset.assigned')->exists())->toBeTrue();
    expect(AuditLog::where('action', 'asset.returned')->exists())->toBeTrue();
});
