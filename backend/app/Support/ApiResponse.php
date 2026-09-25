<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Standard {data, meta} success envelope used by every API endpoint
 * (see docs/api-design.md §1). Validation/auth errors use Laravel's native
 * exception rendering and are not wrapped here.
 */
class ApiResponse
{
    public static function data(mixed $data, ?array $meta = null, int $status = 200): JsonResponse
    {
        $payload = ['data' => $data];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
