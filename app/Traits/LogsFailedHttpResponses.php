<?php

namespace App\Traits;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Logs a warning including body + status and throws when a external HTTP call failed.
 */
trait LogsFailedHttpResponses
{
    private function throwIfFailed(Response $response, string $message): void
    {
        if ($response->failed()) {
            Log::warning($message, [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $response->throw();
        }
    }
}
