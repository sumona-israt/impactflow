<?php

namespace App\Contracts;

/**
 * Low-level RPC transport — no business meaning, just "call this Odoo method
 * with these args" (see docs/odoo-integration.md §2). Bound to FakeOdooClient
 * or OdooJsonRpcClient depending on config('odoo.mode').
 */
interface OdooClientInterface
{
    public function authenticate(): int;

    /**
     * @param  list<mixed>  $args
     * @param  array<string, mixed>  $kwargs
     */
    public function callKw(string $model, string $method, array $args, array $kwargs = []): mixed;
}
