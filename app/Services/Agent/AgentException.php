<?php

namespace App\Services\Agent;

use RuntimeException;

class AgentException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Gemini is not configured. Add GEMINI_API_KEY to the environment.');
    }

    public static function busy(): self
    {
        return new self('Gemini is busy right now. Wait a moment and ask again.');
    }

    public static function quota(): self
    {
        return new self('The free Gemini quota for this model is used up. Wait and try again, or switch models in the environment.');
    }

    public static function declined(): self
    {
        return new self('Gemini declined that question. Rephrase it and try again.');
    }

    public static function unreachable(): self
    {
        return new self('Gemini could not be reached. Check the connection and try again.');
    }

    public static function badResponse(): self
    {
        return new self('Gemini returned an answer this app could not read. Try again.');
    }
}
