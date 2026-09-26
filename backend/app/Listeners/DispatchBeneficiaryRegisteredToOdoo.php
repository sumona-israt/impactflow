<?php

namespace App\Listeners;

use App\Events\BeneficiaryRegistered;
use App\Jobs\OdooSyncJob;

class DispatchBeneficiaryRegisteredToOdoo
{
    public function handle(BeneficiaryRegistered $event): void
    {
        OdooSyncJob::dispatch($event->beneficiary::class, $event->beneficiary->id)->afterCommit();
    }
}
