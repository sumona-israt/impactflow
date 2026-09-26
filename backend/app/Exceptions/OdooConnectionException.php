<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by any OdooClientInterface implementation on a transport-level
 * failure (network error, non-2xx response, malformed RPC result, or a
 * simulated mock-mode failure). Caught by OdooSyncService, which records it
 * on the sync log row and re-throws so OdooSyncJob's queue retry takes over.
 */
class OdooConnectionException extends RuntimeException {}
