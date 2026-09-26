<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attaches a request id (and, once authenticated, the acting user id) to
 * Laravel's Context facade — automatically included in every log entry and
 * exception report for the rest of the request, with no per-call-site
 * plumbing (see docs/implementation-plan.md Phase 9).
 */
class AddRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        Context::add('request_id', (string) Str::uuid());
        Context::add('user_id', $request->user()?->id);

        return $next($request);
    }
}
