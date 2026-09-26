<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | This app is served entirely through nginx as a single origin (see
    | docs/architecture.md §1/§3) — the Next.js frontend and the Laravel API
    | share one registrable origin, so no cross-origin browser requests are
    | expected. Pinning `allowed_origins` to APP_URL explicitly, rather than
    | leaving this file absent (which happened to no-op safely by accident —
    | see docs/implementation-plan.md Phase 9 — but wasn't a documented,
    | intentional restriction).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('APP_URL', 'http://localhost')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
