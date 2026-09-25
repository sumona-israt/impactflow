<?php

namespace App\Actions\Expenses;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Services\Audit\AuditLogger;
use App\Services\Workflow\WorkflowService;
use Illuminate\Validation\ValidationException;

class SubmitExpenseAction
{
    public function __construct(
        private readonly WorkflowService $workflowService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(Expense $expense): Expense
    {
        if ($expense->status !== ExpenseStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'This expense has already been submitted.',
            ]);
        }

        $this->workflowService->start($expense, 'Expense Approval');

        $this->auditLogger->log('expense.submitted', Expense::class, $expense->id, [], [
            'amount' => (string) $expense->amount,
            'program_id' => $expense->program_id,
        ]);

        return $expense->fresh();
    }
}
