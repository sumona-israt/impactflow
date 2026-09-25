<?php

namespace App\Contracts;

use App\Models\WorkflowInstance;

/**
 * Implemented by any model whose status is driven by the generic approval
 * workflow engine (see docs/database-design.md §8). WorkflowInstanceController
 * calls this after recording a decision so the engine itself never needs to
 * know about Expense (or any other) entity-specific status values.
 */
interface Workflowable
{
    public function syncWorkflowStatus(WorkflowInstance $instance): void;
}
