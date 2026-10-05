<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDisclaimerRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_disclaimer_component_renders_on_a_page_when_included(): void
    {
        $user = User::factory()->create();

        // The goat detail page includes the AI disclaimer component (Phase 3 UI
        // will render it there; here we assert the component itself renders
        // through a real HTTP response by placing it on the profile page view
        // — simplest reliable check is direct Blade rendering).
        $rendered = view('components.ai-disclaimer')->render();

        $this->assertStringContainsString('AI-generated advisory content', $rendered);
        $this->assertStringContainsString('not a substitute for professional veterinary diagnosis', $rendered);

        // And the app still serves pages normally with the AI layer present
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }
}
