<?php

namespace App\Events;

use App\Models\Program;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a program's status moves to ProgramStatus::Approved (see
 * App\Actions\Programs\UpdateProgramStatusAction). Consumed by
 * App\Listeners\DispatchProgramApprovedToOdoo — see docs/odoo-integration.md.
 */
class ProgramApproved
{
    use Dispatchable;

    public function __construct(public readonly Program $program) {}
}
