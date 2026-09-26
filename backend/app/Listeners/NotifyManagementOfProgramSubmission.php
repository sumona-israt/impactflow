<?php

namespace App\Listeners;

use App\Enums\RoleEnum;
use App\Events\ProgramSubmittedForApproval;
use App\Models\User;
use App\Notifications\ProgramAwaitingApproval;

class NotifyManagementOfProgramSubmission
{
    public function handle(ProgramSubmittedForApproval $event): void
    {
        $recipients = User::role(RoleEnum::Management->value)->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new ProgramAwaitingApproval($event->program));
        }
    }
}
