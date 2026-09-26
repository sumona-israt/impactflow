<?php

namespace App\Notifications;

use App\Models\WorkflowInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every user holding the current step's required role — generic
 * across any Workflowable entity, no entity-specific copy (see
 * docs/database-design.md §11).
 */
class WorkflowActionNeeded extends Notification implements ShouldQueue
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
            ->subject("Action needed: {$this->entityLabel()}")
            ->line("{$this->entityLabel()} is awaiting your review at the \"{$this->instance->currentStep->name}\" step of the \"{$this->instance->workflow->name}\" workflow.");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->entityLabel()} is awaiting your review.",
            'entity_type' => class_basename($this->instance->entity_type),
            'entity_id' => $this->instance->entity_id,
        ];
    }

    private function entityLabel(): string
    {
        return class_basename($this->instance->entity_type)." #{$this->instance->entity_id}";
    }
}
