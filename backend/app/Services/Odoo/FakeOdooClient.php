<?php

namespace App\Services\Odoo;

use App\Contracts\OdooClientInterface;
use App\Exceptions\OdooConnectionException;

/**
 * ODOO_MODE=mock's transport (see docs/odoo-integration.md §5). No network
 * call is made — `create` returns a deterministic, realistic-looking fake
 * Odoo id (stable across retries/re-syncs of the same record, not random
 * each time), `write` returns true. ODOO_MOCK_FAILURE_RATE lets a demo show
 * retry/recovery behavior honestly, without a real server to take down.
 */
class FakeOdooClient implements OdooClientInterface
{
    public function authenticate(): int
    {
        return 1;
    }

    public function callKw(string $model, string $method, array $args, array $kwargs = []): mixed
    {
        $this->maybeSimulateFailure();

        return match ($method) {
            'create' => $this->fakeId($model, $args[0] ?? []),
            'write' => true,
            default => [],
        };
    }

    private function maybeSimulateFailure(): void
    {
        $failureRate = (float) config('odoo.mock_failure_rate', 0);

        if ($failureRate > 0 && (mt_rand() / mt_getrandmax()) < $failureRate) {
            throw new OdooConnectionException('Simulated Odoo transport failure (ODOO_MOCK_FAILURE_RATE).');
        }
    }

    /**
     * Deterministic per (model, payload identity) so repeated syncs of the
     * same local record always resolve to the same fake Odoo id.
     */
    private function fakeId(string $model, array $payload): int
    {
        $identity = $model.':'.json_encode($payload);

        return (crc32($identity) % 900000) + 100000;
    }
}
