<?php

namespace App\Support;

use Closure;
use Throwable;

class PostCommitNotification
{
    /** The account/profile is already saved. Report mail failure without claiming the save failed. */
    public static function attempt(Closure $send)
    {
        try {
            $send();
            return true;
        } catch (Throwable $e) {
            try {
                report($e);
            } catch (Throwable $loggingFailure) {
                SecondaryFailure::report('Verification mail reporting failed', $e, $loggingFailure);
            }
            return false;
        }
    }
}
