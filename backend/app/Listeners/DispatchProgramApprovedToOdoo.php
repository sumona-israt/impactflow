<?php

namespace App\Listeners;

use App\Events\ProgramApproved;
use App\Jobs\OdooSyncJob;

/**
 * Does no I/O itself — only queues OdooSyncJob, so the rest of the
 * application never blocks on Odoo (see docs/odoo-integration.md §7).
 */
class DispatchProgramApprovedToOdoo
{
    public function handle(ProgramApproved $event): void
    {
        OdooSyncJob::dispatch($event->program::class, $event->program->id)->afterCommit();
    }
}
