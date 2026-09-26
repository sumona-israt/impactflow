<?php

namespace App\Listeners;

use App\Events\ExpenseApproved;
use App\Jobs\OdooSyncJob;

class DispatchExpenseApprovedToOdoo
{
    public function handle(ExpenseApproved $event): void
    {
        OdooSyncJob::dispatch($event->expense::class, $event->expense->id)->afterCommit();
    }
}
