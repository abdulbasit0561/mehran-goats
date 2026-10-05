<?php

namespace Tests\Unit;

use App\Services\Ai\AiHealthAdvisor;
use Tests\TestCase;

class AiAdvisorPromptTest extends TestCase
{
    public function test_system_prompt_demands_json_and_advisory_disclaimer(): void
    {
        $method = new \ReflectionMethod(AiHealthAdvisor::class, 'systemPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke(null);

        $this->assertStringContainsString('strict JSON', $prompt);
        $this->assertStringContainsString('advisory', $prompt);
        $this->assertStringContainsString('not a substitute', $prompt);
        $this->assertStringContainsString('veterinar', $prompt);
    }

    public function test_goat_schema_is_described(): void
    {
        $method = new \ReflectionMethod(AiHealthAdvisor::class, 'systemPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke(null);

        $this->assertStringContainsString('insights', $prompt);
        $this->assertStringContainsString('suggestions', $prompt);
        $this->assertStringContainsString('severity', $prompt);
        $this->assertStringContainsString('info', $prompt);
        $this->assertStringContainsString('warning', $prompt);
        $this->assertStringContainsString('alert', $prompt);
    }
}
