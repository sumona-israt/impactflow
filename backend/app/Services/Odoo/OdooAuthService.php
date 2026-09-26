<?php

namespace App\Services\Odoo;

use App\Contracts\OdooClientInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Caches the authenticated Odoo uid so every sync doesn't re-authenticate
 * (see docs/odoo-integration.md §2). Mock mode never hits the cache-miss
 * path for real — FakeOdooClient::authenticate() returns instantly.
 */
class OdooAuthService
{
    public function __construct(private readonly OdooClientInterface $client) {}

    public function uid(): int
    {
        if (config('odoo.mode') !== 'live') {
            return $this->client->authenticate();
        }

        return Cache::remember('odoo:uid', now()->addHours(4), fn () => $this->client->authenticate());
    }
}
