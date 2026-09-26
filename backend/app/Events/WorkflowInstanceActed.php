<?php

namespace App\Events;

use App\Enums\WorkflowDecision;
use App\Models\WorkflowInstance;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired by the generic workflow engine (App\Services\Workflow\WorkflowService)
 * after every recorded decision — advance, final approval, reject, or return.
 * Entity-agnostic; App\Events\ExpenseApproved fires separately (for the Odoo
 * sync hook only) and is unaffected by this event. Consumed by
 * App\Listeners\NotifyWorkflowParticipants.
 */
class WorkflowInstanceActed
{
    use Dispatchable;

    public function __construct(
        public readonly WorkflowInstance $instance,
        public readonly WorkflowDecision $decision,
    ) {}
}
