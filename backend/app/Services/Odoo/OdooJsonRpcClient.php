<?php

namespace App\Services\Odoo;

use App\Contracts\OdooClientInterface;
use App\Exceptions\OdooConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * ODOO_MODE=live's transport — Odoo's JSON-RPC 2 endpoint via Laravel's Http
 * facade (see docs/odoo-integration.md §2; JSON-RPC chosen over XML-RPC
 * since ext-xmlrpc isn't installed in this image and JSON-RPC needs no
 * extension or extra package).
 */
class OdooJsonRpcClient implements OdooClientInterface
{
    public function authenticate(): int
    {
        $result = $this->call('common', 'login', [
            config('odoo.database'),
            config('odoo.username'),
            config('odoo.password'),
        ]);

        if (! is_int($result)) {
            throw new OdooConnectionException('Odoo authentication failed — check ODOO_DATABASE/ODOO_USERNAME/ODOO_PASSWORD.');
        }

        return $result;
    }

    public function callKw(string $model, string $method, array $args, array $kwargs = []): mixed
    {
        $uid = app(OdooAuthService::class)->uid();

        return $this->call('object', 'execute_kw', [
            config('odoo.database'),
            $uid,
            config('odoo.password'),
            $model,
            $method,
            $args,
            $kwargs,
        ]);
    }

    private function call(string $service, string $method, array $args): mixed
    {
        $response = Http::timeout(15)->post(rtrim((string) config('odoo.base_url'), '/').'/jsonrpc', [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'params' => [
                'service' => $service,
                'method' => $method,
                'args' => $args,
            ],
            'id' => (string) Str::uuid(),
        ]);

        if ($response->failed()) {
            throw new OdooConnectionException("Odoo JSON-RPC request failed with status {$response->status()}.");
        }

        $body = $response->json();

        if (isset($body['error'])) {
            $message = $body['error']['data']['message'] ?? $body['error']['message'] ?? 'Unknown Odoo error.';
            throw new OdooConnectionException("Odoo error: {$message}");
        }

        return $body['result'] ?? null;
    }
}
