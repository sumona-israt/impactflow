<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, OrganizationSeeder::class]);

    $this->hrOfficer = User::factory()->create();
    $this->hrOfficer->assignRole(RoleEnum::HrAdminOfficer->value);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(RoleEnum::SuperAdmin->value);
});

test('any authenticated staff can list departments and branches', function () {
    $this->actingAs($this->fieldOfficer)->getJson('/api/v1/departments')->assertOk();
    $this->actingAs($this->fieldOfficer)->getJson('/api/v1/branches')->assertOk();
    $this->actingAs($this->fieldOfficer)->getJson('/api/v1/program-categories')->assertOk();
});

test('only hr/admin officer (or super admin) can manage departments and branches', function () {
    $this->actingAs($this->hrOfficer)
        ->postJson('/api/v1/departments', ['name' => 'Advocacy'])
        ->assertCreated();

    $this->actingAs($this->fieldOfficer)
        ->postJson('/api/v1/departments', ['name' => 'Should Fail'])
        ->assertForbidden();
});

test('only a super admin can manage program categories by default', function () {
    $created = $this->actingAs($this->superAdmin)
        ->postJson('/api/v1/program-categories', ['name' => 'Livelihoods II'])
        ->assertCreated()
        ->json('data');

    $this->actingAs($this->superAdmin)
        ->putJson("/api/v1/program-categories/{$created['id']}", ['name' => 'Livelihoods III'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Livelihoods III');

    $this->actingAs($this->hrOfficer)
        ->postJson('/api/v1/program-categories', ['name' => 'Should Fail'])
        ->assertForbidden();

    $this->actingAs($this->fieldOfficer)
        ->putJson("/api/v1/program-categories/{$created['id']}", ['name' => 'Should Also Fail'])
        ->assertForbidden();
});

test('program category names must be unique', function () {
    $this->actingAs($this->superAdmin)
        ->postJson('/api/v1/program-categories', ['name' => 'Education'])
        ->assertCreated();

    $this->actingAs($this->superAdmin)
        ->postJson('/api/v1/program-categories', ['name' => 'Education'])
        ->assertUnprocessable();
});
