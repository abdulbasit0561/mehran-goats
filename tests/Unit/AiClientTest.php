<?php

namespace Tests\Unit;

use App\Services\Ai\AiClient;
use App\Services\Ai\AiClientException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiClientTest extends TestCase
{
    private function payload(array $content = ['status' => 'ok']): array
    {
        return ['choices' => [['message' => ['content' => json_encode($content)]]]];
    }

    public function test_successful_json_response_is_parsed(): void
    {
        config(['ai.base_url' => 'https://ai.example.test', 'ai.api_key' => 'sk-test', 'ai.model' => 'm1']);

        Http::fake(['ai.example.test/*' => Http::response($this->payload(['insights' => [], 'suggestions' => []]), 200)]);

        $client = new AiClient;
        $result = $client->chat([['role' => 'user', 'content' => 'hi']]);

        $this->assertSame(['insights' => [], 'suggestions' => []], $result);
    }

    public function test_json_in_markdown_fence_is_parsed(): void
    {
        config(['ai.base_url' => 'https://ai.example.test', 'ai.api_key' => 'sk-test', 'ai.model' => 'm1']);

        Http::fake(['ai.example.test/*' => Http::response([
            'choices' => [['message' => ['content' => "```json\n{\"status\":\"ok\"}\n```"]]],
        ], 200)]);

        $result = (new AiClient)->chat([['role' => 'user', 'content' => 'hi']]);

        $this->assertSame(['status' => 'ok'], $result);
    }

    public function test_server_error_is_retried_then_mapped_to_friendly_exception(): void
    {
        config(['ai.base_url' => 'https://ai.example.test', 'ai.api_key' => 'sk-test', 'ai.model' => 'm1']);

        Http::fake([
            'ai.example.test/*' => Http::sequence()
                ->push($this->payload(), 500)
                ->push($this->payload(), 500),
        ]);

        $this->expectException(AiClientException::class);
        $this->expectExceptionMessage('AI service is temporarily unavailable. Please try again shortly.');

        (new AiClient)->chat([['role' => 'user', 'content' => 'hi']]);
    }

    public function test_rate_limit_is_retried(): void
    {
        config(['ai.base_url' => 'https://ai.example.test', 'ai.api_key' => 'sk-test', 'ai.model' => 'm1']);

        Http::fake([
            'ai.example.test/*' => Http::sequence()
                ->push(['error' => 'rate limited'], 429)
                ->push($this->payload(['ok' => true]), 200),
        ]);

        $result = (new AiClient)->chat([['role' => 'user', 'content' => 'hi']]);

        $this->assertSame(['ok' => true], $result);
    }

    public function test_invalid_json_triggers_repair_retry_then_fails(): void
    {
        config(['ai.base_url' => 'https://ai.example.test', 'ai.api_key' => 'sk-test', 'ai.model' => 'm1']);

        Http::fake([
            'ai.example.test/*' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => 'not json at all']]]], 200)
                ->push(['choices' => [['message' => ['content' => 'still not json']]]], 200),
        ]);

        try {
            (new AiClient)->chat([['role' => 'user', 'content' => 'hi']]);
            $this->fail('Expected AiClientException');
        } catch (AiClientException $e) {
            $this->assertStringNotContainsString('sk-test', $e->getMessage());
            $this->assertStringNotContainsString('ai.example.test', $e->getMessage());
        }
    }

    public function test_missing_api_key_reports_not_configured(): void
    {
        config(['ai.api_key' => null]);

        $this->expectException(AiClientException::class);
        $this->expectExceptionMessage('AI is not configured. Please contact the administrator.');

        (new AiClient)->chat([['role' => 'user', 'content' => 'hi']]);
    }

    public function test_connection_error_is_friendly(): void
    {
        config(['ai.base_url' => 'https://ai.example.test', 'ai.api_key' => 'sk-test', 'ai.model' => 'm1']);

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('cURL error 28 to https://ai.example.test/v1/chat/completions');
        });

        try {
            (new AiClient)->chat([['role' => 'user', 'content' => 'hi']]);
            $this->fail('Expected AiClientException');
        } catch (AiClientException $e) {
            $this->assertSame('AI service is unreachable. Please try again in a moment.', $e->getMessage());
        }
    }
}
