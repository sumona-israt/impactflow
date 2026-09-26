<?php

use App\Enums\RoleEnum;
use App\Models\Expense;
use App\Models\Program;
use App\Models\User;
use App\Notifications\WorkflowActionNeeded;
use App\Notifications\WorkflowDecisionRecorded;
use Database\Seeders\ApprovalWorkflowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, ApprovalWorkflowSeeder::class]);

    $this->fieldOfficer = User::factory()->create();
    $this->fieldOfficer->assignRole(RoleEnum::FieldOfficer->value);

    $this->programManager = User::factory()->create();
    $this->programManager->assignRole(RoleEnum::ProgramManager->value);

    $this->financeOfficer = User::factory()->create();
    $this->financeOfficer->assignRole(RoleEnum::FinanceOfficer->value);

    $this->program = Program::factory()->create(['budget' => 10000]);
});

function createAndSubmitExpense($test, User $submitter, Program $program, float $amount = 2000): Expense
{
    $response = $test->actingAs($submitter)->postJson('/api/v1/expenses', [
        'program_id' => $program->id,
        'amount' => $amount,
        'expense_date' => now()->toDateString(),
        'description' => 'Test expense',
    ])->assertCreated();

    $expense = Expense::findOrFail($response->json('data.id'));

    $test->actingAs($submitter)->postJson("/api/v1/expenses/{$expense->id}/submit")->assertOk();

    return $expense->fresh();
}

test('submitting an expense notifies the program manager, not finance or the submitter', function () {
    Notification::fake();

    createAndSubmitExpense($this, $this->fieldOfficer, $this->program);

    Notification::assertSentTo($this->programManager, WorkflowActionNeeded::class);
    Notification::assertNotSentTo($this->financeOfficer, WorkflowActionNeeded::class);
    Notification::assertNotSentTo($this->fieldOfficer, WorkflowDecisionRecorded::class);
});

test("the program manager's approval notifies the finance officer, not the submitter", function () {
    $expense = createAndSubmitExpense($this, $this->fieldOfficer, $this->program);
    $instanceId = $expense->latestWorkflowInstance()->id;

    Notification::fake();

    $this->actingAs($this->programManager)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'approve'])
        ->assertOk();

    Notification::assertSentTo($this->financeOfficer, WorkflowActionNeeded::class);
    Notification::assertNotSentTo($this->fieldOfficer, WorkflowDecisionRecorded::class);
    Notification::assertNotSentTo($this->programManager, WorkflowActionNeeded::class);
});

test('the final approval notifies the submitter with an approved decision, not either reviewer', function () {
    $expense = createAndSubmitExpense($this, $this->fieldOfficer, $this->program);
    $instanceId = $expense->latestWorkflowInstance()->id;

    $this->actingAs($this->programManager)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'approve'])
        ->assertOk();

    Notification::fake();

    $this->actingAs($this->financeOfficer)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'approve'])
        ->assertOk();

    Notification::assertSentTo(
        $this->fieldOfficer,
        WorkflowDecisionRecorded::class,
        fn (WorkflowDecisionRecorded $notification) => $notification->toArray($this->fieldOfficer)['message'] === "Your Expense #{$expense->id} was approved.",
    );
    Notification::assertNotSentTo($this->programManager, WorkflowDecisionRecorded::class);
    Notification::assertNotSentTo($this->financeOfficer, WorkflowDecisionRecorded::class);
});

test('a rejection notifies the submitter with a rejected decision', function () {
    $expense = createAndSubmitExpense($this, $this->fieldOfficer, $this->program);
    $instanceId = $expense->latestWorkflowInstance()->id;

    Notification::fake();

    $this->actingAs($this->programManager)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'reject'])
        ->assertOk();

    Notification::assertSentTo(
        $this->fieldOfficer,
        WorkflowDecisionRecorded::class,
        fn (WorkflowDecisionRecorded $notification) => $notification->toArray($this->fieldOfficer)['message'] === "Your Expense #{$expense->id} was rejected.",
    );
});
