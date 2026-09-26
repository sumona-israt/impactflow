<?php

namespace App\Notifications;

use App\Models\Program;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProgramAwaitingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Program $program) {}

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
            ->subject("Program awaiting approval: {$this->program->name}")
            ->line("The program \"{$this->program->name}\" has been submitted for approval.");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "The program \"{$this->program->name}\" has been submitted for approval.",
            'entity_type' => 'Program',
            'entity_id' => $this->program->id,
        ];
    }
}
