<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class SecondaryFailure
{
    public static function report($message, Throwable $original, Throwable $secondary)
    {
        try {
            Log::warning($message, ['original_exception' => get_class($original), 'cleanup_exception' => get_class($secondary)]);
        } catch (Throwable $ignored) {
            // A logging failure must never replace the original exception.
        }
    }
}
