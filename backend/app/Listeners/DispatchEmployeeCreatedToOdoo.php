<?php

namespace App\Listeners;

use App\Events\EmployeeCreated;
use App\Jobs\OdooSyncJob;

class DispatchEmployeeCreatedToOdoo
{
    public function handle(EmployeeCreated $event): void
    {
        OdooSyncJob::dispatch($event->employee::class, $event->employee->id)->afterCommit();
    }
}
