<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Raised when generation cannot be completed: provider failure, malformed
 * JSON, empty output, or word counts outside the allowed band. Safe messages
 * only — never includes credentials or raw provider payloads.
 */
class GenerationException extends RuntimeException
{
    //
}
