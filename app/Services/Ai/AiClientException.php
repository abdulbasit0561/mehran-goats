<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * User-facing AI failure. The message is ALWAYS safe to show to end users —
 * it never contains API URLs, keys, stack traces or upstream error bodies.
 */
class AiClientException extends RuntimeException
{
    public static function unavailable(): self
    {
        return new self('AI service is temporarily unavailable. Please try again shortly.');
    }

    public static function unreachable(): self
    {
        return new self('AI service is unreachable. Please try again in a moment.');
    }

    public static function notConfigured(): self
    {
        return new self('AI is not configured. Please contact the administrator.');
    }

    public static function badResponse(): self
    {
        return new self('AI returned an unexpected response. Please try again.');
    }
}
