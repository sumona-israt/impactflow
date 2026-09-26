<?php

namespace App\Events;

use App\Models\Program;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a program's status moves to ProgramStatus::PendingApproval (see
 * App\Actions\Programs\UpdateProgramStatusAction). Consumed by
 * App\Listeners\NotifyManagementOfProgramSubmission.
 */
class ProgramSubmittedForApproval
{
    use Dispatchable;

    public function __construct(public readonly Program $program) {}
}
