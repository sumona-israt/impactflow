<?php

namespace App\Actions\Expenses;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Services\Audit\AuditLogger;
use Illuminate\Validation\ValidationException;

class UpdateExpenseAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Expense $expense, array $attributes): Expense
    {
        if ($expense->status !== ExpenseStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only a draft expense can be edited — it is currently under review.',
            ]);
        }

        $expense->update($attributes);

        if ($expense->wasChanged()) {
            $this->auditLogger->logUpdated($expense, 'expense.updated');
        }

        return $expense;
    }
}
