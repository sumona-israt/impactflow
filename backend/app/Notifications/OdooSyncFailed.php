<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when App\Jobs\OdooSyncJob exhausts all retries (see
 * docs/odoo-integration.md §7).
 */
class OdooSyncFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $entityType,
        private readonly string $localId,
        private readonly string $errorMessage,
    ) {}

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
            ->subject('Odoo sync failed')
            ->line("Syncing {$this->entityLabel()} to Odoo failed after all retries: {$this->errorMessage}");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Syncing {$this->entityLabel()} to Odoo failed: {$this->errorMessage}",
        ];
    }

    private function entityLabel(): string
    {
        return class_basename($this->entityType)." #{$this->localId}";
    }
}
