<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->financeOfficer = User::factory()->create();
    $this->financeOfficer->assignRole(RoleEnum::FinanceOfficer->value);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);
});

test('any staff with expense visibility can list expense categories', function () {
    $this->actingAs($this->fieldOfficer)->getJson('/api/v1/expense-categories')->assertOk();
});

test('only finance officer (or super admin) can manage expense categories', function () {
    $this->actingAs($this->financeOfficer)
        ->postJson('/api/v1/expense-categories', ['name' => 'Fuel'])
        ->assertCreated();

    $this->actingAs($this->fieldOfficer)
        ->postJson('/api/v1/expense-categories', ['name' => 'Should Fail'])
        ->assertForbidden();
});
