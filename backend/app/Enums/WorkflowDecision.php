<?php

namespace App\Enums;

/**
 * The action an actor takes on a workflow instance's current step. Deliberately
 * not named "WorkflowAction" to avoid confusion with the Eloquent model of
 * that name (App\Models\WorkflowAction), which is the persisted record of a
 * decision, not the decision type itself.
 */
enum WorkflowDecision: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Return = 'return';
}
