<?php

namespace App\Listeners;

use App\Contracts\Workflowable;
use App\Enums\WorkflowInstanceStatus;
use App\Events\WorkflowInstanceActed;
use App\Events\WorkflowInstanceStarted;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Notifications\WorkflowActionNeeded;
use App\Notifications\WorkflowDecisionRecorded;

/**
 * Entity-agnostic — works for any Workflowable entity via the generic
 * WorkflowInstance/WorkflowStep shape, not just Expense (see
 * docs/database-design.md §11).
 */
class NotifyWorkflowParticipants
{
    public function handleStarted(WorkflowInstanceStarted $event): void
    {
        $this->notifyCurrentStepHolders($event->instance);
    }

    public function handleActed(WorkflowInstanceActed $event): void
    {
        $instance = $event->instance;

        if ($instance->status === WorkflowInstanceStatus::InProgress) {
            $this->notifyCurrentStepHolders($instance);

            return;
        }

        $entity = $instance->entity;

        if ($entity instanceof Workflowable) {
            $entity->workflowOwner()?->notify(new WorkflowDecisionRecorded($instance));
        }
    }

    private function notifyCurrentStepHolders(WorkflowInstance $instance): void
    {
        $recipients = User::role($instance->currentStep->role_required)->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new WorkflowActionNeeded($instance));
        }
    }
}
