<?php

namespace App\Console\Commands;

use App\Contracts\OdooClientInterface;
use App\Services\Odoo\OdooHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * The Odoo -> ImpactFlow half of the sync strategy (see
 * docs/odoo-integration.md §6): a connectivity probe plus, in live mode, a
 * change count per mapped model since the last poll. Deliberately does not
 * write inbound changes back into ImpactFlow — reverse field mapping and
 * conflict resolution is a materially separate feature; detecting and
 * reporting drift is the complete slice this command implements.
 */
class PollOdooCommand extends Command
{
    protected $signature = 'odoo:poll';

    protected $description = 'Probe Odoo connectivity and log how many mapped records changed upstream since the last poll';

    public function handle(OdooHealthService $health, OdooClientInterface $client): int
    {
        if (config('odoo.mode') !== 'live') {
            $this->info('ODOO_MODE=mock — nothing to poll against.');

            return self::SUCCESS;
        }

        $status = $health->status();

        if (! $status['connected']) {
            $this->error('Odoo is unreachable — skipping poll.');

            return self::FAILURE;
        }

        $lastPolledAt = Cache::get('odoo:last-polled-at');
        $since = $lastPolledAt ?? now()->subDay()->toIso8601String();

        foreach (config('odoo.mappings') as $mapping) {
            try {
                $count = $client->callKw($mapping['model'], 'search_count', [
                    [['write_date', '>', $since]],
                ]);

                $this->info("{$mapping['model']}: {$count} record(s) changed since {$since}.");
            } catch (Throwable $e) {
                $this->error("{$mapping['model']}: poll failed — {$e->getMessage()}");
            }
        }

        Cache::put('odoo:last-polled-at', now()->toIso8601String());

        return self::SUCCESS;
    }
}
