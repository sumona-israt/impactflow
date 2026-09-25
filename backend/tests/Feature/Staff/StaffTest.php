<?php

use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->hrOfficer = User::factory()->create();
    $this->hrOfficer->assignRole(RoleEnum::HrAdminOfficer->value);

    $this->programManager = User::factory()->create();
    $this->programManager->assignRole(RoleEnum::ProgramManager->value);
});

test('an hr officer can create an employee and it is audited', function () {
    $this->actingAs($this->hrOfficer)
        ->postJson('/api/v1/employees', ['name' => 'Karim Hossain', 'position' => 'Field Coordinator'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Karim Hossain');

    expect(AuditLog::where('action', 'employee.created')->exists())->toBeTrue();
});

test('a program manager cannot manage employees', function () {
    $this->actingAs($this->programManager)
        ->postJson('/api/v1/employees', ['name' => 'Should Fail'])
        ->assertForbidden();
});

test('an hr officer can create a volunteer', function () {
    $this->actingAs($this->hrOfficer)
        ->postJson('/api/v1/volunteers', ['full_name' => 'Nusrat Jahan', 'skills' => ['teaching']])
        ->assertCreated()
        ->assertJsonPath('data.full_name', 'Nusrat Jahan')
        ->assertJsonPath('data.skills.0', 'teaching');
});
