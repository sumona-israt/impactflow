<?php

use App\Enums\ExpenseStatus;
use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ApprovalWorkflowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

function submitExpense($test, User $submitter, Program $program, float $amount = 2000): Expense
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

test('a field officer can create and submit an expense, starting the workflow', function () {
    $expense = submitExpense($this, $this->fieldOfficer, $this->program);

    expect($expense->status->value)->toBe('program_review');
    expect(AuditLog::where('action', 'expense.created')->exists())->toBeTrue();
    expect(AuditLog::where('action', 'expense.submitted')->exists())->toBeTrue();
});

test('the full two-step approval chain works end to end', function () {
    $expense = submitExpense($this, $this->fieldOfficer, $this->program, 3000);
    $instanceId = $expense->latestWorkflowInstance()->id;

    // Wrong role cannot act
    $this->actingAs($this->financeOfficer)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'approve'])
        ->assertUnprocessable();

    // Program manager approves step 1
    $this->actingAs($this->programManager)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'approve', 'comment' => 'Looks good'])
        ->assertOk()
        ->assertJsonPath('data.current_step.name', 'finance_review');

    expect($expense->fresh()->status->value)->toBe('finance_review');

    // Finance officer approves step 2 (final)
    $this->actingAs($this->financeOfficer)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'approve'])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    expect($expense->fresh()->status->value)->toBe('approved');
    expect(AuditLog::where('action', 'workflow.approve')->count())->toBe(2);
});

test('finance approval is blocked when it would exceed the program budget', function () {
    $expense = submitExpense($this, $this->fieldOfficer, $this->program, 9000);
    $instanceId = $expense->latestWorkflowInstance()->id;

    $this->actingAs($this->programManager)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'approve'])
        ->assertOk();

    // A second expense already approved, consuming most of the budget
    $other = Expense::factory()->create(['program_id' => $this->program->id, 'status' => ExpenseStatus::Approved, 'amount' => 5000]);

    $response = $this->actingAs($this->financeOfficer)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'approve']);

    $response->assertUnprocessable()->assertJsonValidationErrors('amount');
    expect($expense->fresh()->status->value)->toBe('finance_review');
});

test('a program manager can reject an expense', function () {
    $expense = submitExpense($this, $this->fieldOfficer, $this->program);
    $instanceId = $expense->latestWorkflowInstance()->id;

    $this->actingAs($this->programManager)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'reject', 'comment' => 'Not eligible'])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    expect($expense->fresh()->status->value)->toBe('rejected');
});

test('returning an expense sends it back to draft and it can be resubmitted', function () {
    $expense = submitExpense($this, $this->fieldOfficer, $this->program);
    $instanceId = $expense->latestWorkflowInstance()->id;

    $this->actingAs($this->programManager)
        ->postJson("/api/v1/workflow-instances/{$instanceId}/actions", ['action' => 'return', 'comment' => 'Add more detail'])
        ->assertOk()
        ->assertJsonPath('data.status', 'returned');

    expect($expense->fresh()->status->value)->toBe('draft');

    $this->actingAs($this->fieldOfficer)
        ->postJson("/api/v1/expenses/{$expense->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', 'program_review');

    expect($expense->latestWorkflowInstance()->id)->not->toBe($instanceId);
});

test('only a draft expense can be edited', function () {
    $expense = submitExpense($this, $this->fieldOfficer, $this->program);

    $this->actingAs($this->fieldOfficer)
        ->putJson("/api/v1/expenses/{$expense->id}", ['amount' => 5000])
        ->assertUnprocessable();
});

test('a field officer can upload and download a receipt attachment', function () {
    Storage::fake('local');

    $expense = Expense::factory()->create(['submitted_by' => $this->fieldOfficer->id]);
    $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

    $upload = $this->actingAs($this->fieldOfficer)
        ->post("/api/v1/expenses/{$expense->id}/attachments", ['file' => $file])
        ->assertCreated();

    expect(AuditLog::where('action', 'expense.attachment_uploaded')->exists())->toBeTrue();

    $attachmentId = $upload->json('data.id');

    $this->actingAs($this->fieldOfficer)
        ->get("/api/v1/expenses/{$expense->id}/attachments/{$attachmentId}/download")
        ->assertOk();
});

test('another field officer cannot upload a receipt to someone else\'s draft expense', function () {
    Storage::fake('local');

    $expense = Expense::factory()->create(['submitted_by' => $this->fieldOfficer->id]);
    $otherFieldOfficer = User::factory()->create();
    $otherFieldOfficer->assignRole(RoleEnum::FieldOfficer->value);

    $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

    $this->actingAs($otherFieldOfficer)
        ->post("/api/v1/expenses/{$expense->id}/attachments", ['file' => $file])
        ->assertForbidden();
});
