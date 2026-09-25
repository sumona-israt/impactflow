<?php

namespace App\Contracts;

use Illuminate\Validation\ValidationException;

/**
 * Implemented by any entity whose final workflow approval must respect a
 * budget ceiling (see docs/database-design.md §8 — Finance is the budget
 * gatekeeper, checked once at the last step). Keeps
 * App\Actions\Workflow\RecordWorkflowActionAction generic: it calls this
 * without knowing anything about Expense or programs.budget specifically.
 */
interface BudgetConstrained
{
    /**
     * @throws ValidationException if approving this
     *                             entity now would exceed its budget ceiling.
     */
    public function assertWithinBudget(): void;
}
