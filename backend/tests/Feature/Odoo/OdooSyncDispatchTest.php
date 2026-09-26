<?php

use App\Enums\ProgramStatus;
use App\Enums\RoleEnum;
use App\Jobs\OdooSyncJob;
use App\Models\Beneficiary;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ApprovalWorkflowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, ApprovalWorkflowSeeder::class]);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);

    $this->programManager = User::factory()->create();
    $this->programManager->assignRole(RoleEnum::ProgramManager->value);

    $this->financeOfficer = User::factory()->create();
    $this->financeOfficer->assignRole(RoleEnum::FinanceOfficer->value);

    $this->hrOfficer = User::factory()->create();
    $this->hrOfficer->assignRole(RoleEnum::HrAdminOfficer->value);
});

test('registering a beneficiary dispatches an Odoo sync job', function () {
    Queue::fake();

    $response = $this->actingAs($this->fieldOfficer)->postJson('/api/v1/beneficiaries', [
        'full_name' => 'Test Beneficiary',
        'phone' => '01711111111',
        'registration_date' => now()->toDateString(),
    ])->assertCreated();

    Queue::assertPushed(OdooSyncJob::class, fn (OdooSyncJob $job) => $job->entityType === Beneficiary::class
        && $job->localId === $response->json('data.id'));
});

test('creating an employee dispatches an Odoo sync job', function () {
    Queue::fake();

    $response = $this->actingAs($this->hrOfficer)->postJson('/api/v1/employees', [
        'name' => 'Test Employee',
        'position' => 'Field Coordinator',
    ])->assertCreated();

    Queue::assertPushed(OdooSyncJob::class, fn (OdooSyncJob $job) => $job->entityType === Employee::class
        && $job->localId === $response->json('data.id'));
});

test('a program moving to Approved dispatches an Odoo sync job, but earlier transitions do not', function () {
    Queue::fake();

    $program = Program::factory()->create(['status' => ProgramStatus::Draft]);

    $this->actingAs($this->programManager)
        ->patchJson("/api/v1/programs/{$program->id}/status", ['status' => ProgramStatus::PendingApproval->value])
        ->assertOk();

    Queue::assertNotPushed(OdooSyncJob::class);

    $this->actingAs($this->programManager)
        ->patchJson("/api/v1/programs/{$program->id}/status", ['status' => ProgramStatus::Approved->value])
        ->assertOk();

    Queue::assertPushed(OdooSyncJob::class, fn (OdooSyncJob $job) => $job->entityType === Program::class
        && $job->localId === $program->id);
});

test('an expense reaching final workflow approval dispatches an Odoo sync job', function () {
    $program = Program::factory()->create(['budget' => 10000]);

    $response = $this->actingAs($this->fieldOfficer)->postJson('/api/v1/expenses', [
        'program_id' => $program->id,
        'amount' => 2000,
        'expense_date' => now()->toDateString(),
        'description' => 'Test expense',
    ])->assertCreated();
    $expenseId = $response->json('data.id');

    $this->actingAs($this->fieldOfficer)->postJson("/api/v1/expenses/{$expenseId}/submit")->assertOk();

    $instanceId = Expense::findOrFail($expenseId)->latestWorkflowInstance()->id;

    Queue::fake();

    $this->actingAs($this->programManager)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'approve'])
        ->assertOk();

    Queue::assertNotPushed(OdooSyncJob::class);

    $this->actingAs($this->financeOfficer)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'approve'])
        ->assertOk();

    Queue::assertPushed(OdooSyncJob::class, fn (OdooSyncJob $job) => $job->entityType === Expense::class
        && $job->localId === $expenseId);
});
