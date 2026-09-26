<?php

namespace App\Events;

use App\Models\Expense;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when an expense's workflow instance reaches its final approval (see
 * App\Actions\Workflow\RecordWorkflowActionAction). Consumed by
 * App\Listeners\DispatchExpenseApprovedToOdoo — see docs/odoo-integration.md.
 */
class ExpenseApproved
{
    use Dispatchable;

    public function __construct(public readonly Expense $expense) {}
}
