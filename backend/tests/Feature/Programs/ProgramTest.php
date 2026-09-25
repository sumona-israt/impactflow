<?php

use App\Enums\ProgramStatus;
use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);

    $this->programManager = User::factory()->create();
    $this->programManager->assignRole(RoleEnum::ProgramManager->value);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);

    $this->financeOfficer = User::factory()->create();
    $this->financeOfficer->assignRole(RoleEnum::FinanceOfficer->value);
});

test('a program manager can create a program and it is audited', function () {
    $response = $this->actingAs($this->programManager)->postJson('/api/v1/programs', [
        'name' => 'Rural Education Outreach',
        'start_date' => '2026-01-01',
        'budget' => 120000,
        'target_beneficiaries' => 300,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Rural Education Outreach')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.actual_beneficiaries', 0);

    expect(AuditLog::where('action', 'program.created')->exists())->toBeTrue();
});

test('a program with a manager and branch serializes them as objects, not booleans', function () {
    // Regression test: whenLoaded()'s closure was written as
    // `fn () => $this->manager && [...]`, which — because `&&` returns a
    // bool, not its right operand — always serialized a present relation as
    // `true` instead of `{id, name}`. whenLoaded() itself already returns
    // null when the relation is absent, so this only broke the "present" case.
    $organization = Organization::create(['name' => 'Test Org']);
    $branch = Branch::create(['organization_id' => $organization->id, 'name' => 'Dhaka Office']);

    $response = $this->actingAs($this->programManager)->postJson('/api/v1/programs', [
        'name' => 'Program With Branch',
        'start_date' => '2026-01-01',
        'branch_id' => $branch->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.branch.id', $branch->id)
        ->assertJsonPath('data.branch.name', $branch->name);
});

test('a field officer cannot create a program', function () {
    $this->actingAs($this->fieldOfficer)
        ->postJson('/api/v1/programs', ['name' => 'Should Fail', 'start_date' => '2026-01-01'])
        ->assertForbidden();
});

test('a field officer can view programs (read-only access)', function () {
    Program::factory()->create();

    $this->actingAs($this->fieldOfficer)
        ->getJson('/api/v1/programs')
        ->assertOk();
});

test('a finance officer cannot create or update programs but can view them', function () {
    $program = Program::factory()->create();

    $this->actingAs($this->financeOfficer)->getJson('/api/v1/programs')->assertOk();
    $this->actingAs($this->financeOfficer)->getJson("/api/v1/programs/{$program->id}")->assertOk();
    $this->actingAs($this->financeOfficer)
        ->putJson("/api/v1/programs/{$program->id}", ['name' => 'Nope'])
        ->assertForbidden();
});

test('program status can only move through allowed transitions', function () {
    $program = Program::factory()->create(['status' => ProgramStatus::Draft]);

    $this->actingAs($this->programManager)
        ->patchJson("/api/v1/programs/{$program->id}/status", ['status' => ProgramStatus::Active->value])
        ->assertUnprocessable();

    $this->actingAs($this->programManager)
        ->patchJson("/api/v1/programs/{$program->id}/status", ['status' => ProgramStatus::PendingApproval->value])
        ->assertOk()
        ->assertJsonPath('data.status', 'pending_approval');

    expect(AuditLog::where('action', 'program.status_changed')->where('entity_id', $program->id)->exists())->toBeTrue();
});

test('a program manager can enroll and unenroll a beneficiary', function () {
    $program = Program::factory()->create();
    $beneficiary = Beneficiary::factory()->create();

    $enroll = $this->actingAs($this->programManager)
        ->postJson("/api/v1/programs/{$program->id}/beneficiaries", ['beneficiary_id' => $beneficiary->id])
        ->assertCreated();

    expect($program->fresh()->actual_beneficiaries)->toBe(1);

    $enrollmentId = $enroll->json('data.id');

    $this->actingAs($this->programManager)
        ->postJson("/api/v1/programs/{$program->id}/beneficiaries", ['beneficiary_id' => $beneficiary->id])
        ->assertUnprocessable();

    $this->actingAs($this->programManager)
        ->deleteJson("/api/v1/programs/{$program->id}/beneficiaries/{$enrollmentId}")
        ->assertOk()
        ->assertJsonPath('data.status', 'withdrawn');

    expect($program->fresh()->actual_beneficiaries)->toBe(0);
});
