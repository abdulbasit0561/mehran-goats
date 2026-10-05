<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around an OpenAI-compatible chat-completions endpoint.
 *
 * - Retries 429/5xx once with backoff
 * - Demands strict JSON and parses it (tolerates markdown fences)
 * - One "repair" retry when the model returns non-JSON
 * - All failures surface as AiClientException with user-safe messages
 */
class AiClient
{
    public function chat(array $messages, ?float $temperature = null): array
    {
        if (! config('ai.api_key')) {
            throw AiClientException::notConfigured();
        }

        $messages = $this->withSystemPrompt($messages);

        $response = $this->send($messages, $temperature);

        $parsed = $this->parseJson($this->content($response));
        if ($parsed !== null) {
            return $parsed;
        }

        // One repair attempt: ask the model to re-emit valid JSON.
        $repair = $this->send(array_merge($messages, [
            ['role' => 'assistant', 'content' => $this->content($response)],
            ['role' => 'user', 'content' => 'Your previous reply was not valid JSON. Reply again with ONLY the valid JSON object, no prose, no markdown fences.'],
        ]), $temperature);

        $parsed = $this->parseJson($this->content($repair));
        if ($parsed !== null) {
            return $parsed;
        }

        Log::warning('AI response was not parseable as JSON.', ['model' => config('ai.model')]);

        throw AiClientException::badResponse();
    }

    private function send(array $messages, ?float $temperature): object
    {
        $attempts = max(1, (int) config('ai.max_retries'));

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::baseUrl(config('ai.base_url'))
                    ->withToken(config('ai.api_key'))
                    ->timeout(config('ai.timeout'))
                    ->acceptJson()
                    ->post('/v1/chat/completions', [
                        'model' => config('ai.model'),
                        'messages' => $messages,
                        'temperature' => $temperature ?? 0.4,
                        'response_format' => ['type' => 'json_object'],
                    ]);

                if ($response->successful()) {
                    return $response;
                }

                if ($response->status() >= 500 || $response->status() === 429) {
                    if ($attempt < $attempts) {
                        usleep(500_000 * $attempt); // gentle backoff
                        continue;
                    }
                    Log::warning('AI endpoint error.', ['status' => $response->status()]);
                    throw AiClientException::unavailable();
                }

                // 4xx (other than 429) — do not retry
                Log::warning('AI client error.', ['status' => $response->status()]);
                throw AiClientException::badResponse();
            } catch (ConnectionException) {
                if ($attempt < $attempts) {
                    usleep(500_000 * $attempt);
                    continue;
                }
                throw AiClientException::unreachable();
            }
        }

        throw AiClientException::unavailable();
    }

    private function withSystemPrompt(array $messages): array
    {
        // Prepend our JSON-system prompt only if the caller didn't include one.
        if (! empty($messages) && ($messages[0]['role'] ?? null) === 'system') {
            return $messages;
        }

        return array_merge([['role' => 'system', 'content' => 'You must respond with a single valid JSON object only. No prose, no markdown fences.']], $messages);
    }

    private function content(object $response): string
    {
        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw AiClientException::badResponse();
        }

        return $content;
    }

    private function parseJson(string $content): ?array
    {
        $content = trim($content);

        // Strip markdown fences if present
        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $content, $m)) {
            $content = $m[1];
        }

        // Extract the outermost JSON object if the model added prose around it
        if (! str_starts_with($content, '{')) {
            $start = strpos($content, '{');
            $end = strrpos($content, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $content = substr($content, $start, $end - $start + 1);
            }
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }
}
