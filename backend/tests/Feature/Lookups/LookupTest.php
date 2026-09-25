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
