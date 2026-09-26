<?php

namespace App\Notifications;

use App\Enums\WorkflowInstanceStatus;
use App\Models\WorkflowInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the entity's workflowOwner() once its workflow reaches a terminal
 * state (approved/rejected/returned) — see docs/database-design.md §11.
 */
class WorkflowDecisionRecorded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly WorkflowInstance $instance) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->entityLabel()} was {$this->outcome()}")
            ->line("Your {$this->entityLabel()} was {$this->outcome()} in the \"{$this->instance->workflow->name}\" workflow.");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Your {$this->entityLabel()} was {$this->outcome()}.",
            'entity_type' => class_basename($this->instance->entity_type),
            'entity_id' => $this->instance->entity_id,
        ];
    }

    private function entityLabel(): string
    {
        return class_basename($this->instance->entity_type)." #{$this->instance->entity_id}";
    }

    private function outcome(): string
    {
        return match ($this->instance->status) {
            WorkflowInstanceStatus::Approved => 'approved',
            WorkflowInstanceStatus::Rejected => 'rejected',
            WorkflowInstanceStatus::Returned => 'returned for revision',
            WorkflowInstanceStatus::InProgress => 'updated',
        };
    }
}
