<?php

namespace App\Actions\Expenses;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;

class CreateExpenseAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(array $attributes): Expense
    {
        // See CreateProgramAction (Phase 3) for why status is explicit, not a DB default.
        $expense = Expense::create([
            'status' => ExpenseStatus::Draft,
            ...$attributes,
            'submitted_by' => Auth::id(),
        ]);

        $this->auditLogger->logCreated($expense, 'expense.created');

        return $expense;
    }
}
