<?php

namespace App\Events;

use App\Models\WorkflowInstance;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired by the generic workflow engine (App\Services\Workflow\WorkflowService)
 * whenever any Workflowable entity starts a workflow — entity-agnostic, unlike
 * App\Events\ExpenseApproved. Consumed by
 * App\Listeners\NotifyWorkflowParticipants.
 */
class WorkflowInstanceStarted
{
    use Dispatchable;

    public function __construct(public readonly WorkflowInstance $instance) {}
}
