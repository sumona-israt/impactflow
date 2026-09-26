<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\ApprovalWorkflowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, ApprovalWorkflowSeeder::class]);

    $this->financeOfficer = User::factory()->create();
    $this->financeOfficer->assignRole(RoleEnum::FinanceOfficer->value);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);
});

test('a finance officer can list workflow definitions with their steps', function () {
    $response = $this->actingAs($this->financeOfficer)
        ->getJson('/api/v1/workflows')
        ->assertOk();

    $workflow = collect($response->json('data'))->firstWhere('name', 'Expense Approval');

    expect($workflow)->not->toBeNull();
    expect(collect($workflow['steps'])->pluck('name')->all())->toEqual(['program_review', 'finance_review']);
});

test('a field officer cannot list workflow definitions', function () {
    $this->actingAs($this->fieldOfficer)
        ->getJson('/api/v1/workflows')
        ->assertForbidden();
});
