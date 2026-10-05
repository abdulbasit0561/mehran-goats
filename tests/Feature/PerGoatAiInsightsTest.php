<?php

namespace Tests\Feature;

use App\Jobs\RunAiAnalysis;
use App\Models\AiAnalysis;
use App\Models\Breed;
use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PerGoatAiInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
        $this->goat = Goat::create([
            'tag_id'   => 'TAG-AI-101',
            'breed_id' => $this->breed->id,
            'gender'   => 'female',
            'status'   => 'grower',
        ]);
        $this->actingAs($this->user);
    }

    private function makeAnalysis(array $overrides = []): AiAnalysis
    {
        return AiAnalysis::create(array_merge([
            'scope'        => 'goat',
            'goat_id'      => $this->goat->id,
            'insights'     => [
                ['title' => 'Weight trend healthy', 'detail' => 'Gained 4kg in 2 months', 'severity' => 'info'],
                ['title' => 'Vaccination overdue', 'detail' => 'No vaccination in 14 months', 'severity' => 'warning'],
            ],
            'suggestions'  => [
                ['title' => 'Schedule CDT booster', 'detail' => 'Annual booster is due'],
            ],
            'alerts'       => [],
            'recommendations' => [],
            'model'        => 'test-model',
            'generated_at' => now(),
        ], $overrides));
    }

    public function test_dispatch_endpoint_requires_auth(): void
    {
        auth()->logout();

        $this->post(route('goats.ai-insights.store', $this->goat))
            ->assertRedirect('/login');
    }

    public function test_dispatch_endpoint_queues_job(): void
    {
        Queue::fake();

        $this->post(route('goats.ai-insights.store', $this->goat))
            ->assertOk()
            ->assertJson(['status' => 'started']);

        Queue::assertPushed(RunAiAnalysis::class, fn ($job) => $job->scope === 'goat' && $job->goatId === $this->goat->id);
    }

    public function test_dispatch_is_debounced_for_recent_analysis(): void
    {
        Queue::fake();
        $this->makeAnalysis(['generated_at' => now()->subMinute()]);

        $response = $this->post(route('goats.ai-insights.store', $this->goat))
            ->assertOk()
            ->assertJson(['status' => 'debounced']);

        // The existing fresh analysis is returned so the UI can show it immediately
        $this->assertSame('Weight trend healthy', $response->json('analysis.insights.0.title'));

        Queue::assertNotPushed(RunAiAnalysis::class);
    }

    public function test_dispatch_debounces_in_flight_jobs_via_cache(): void
    {
        Queue::fake();
        \Illuminate\Support\Facades\Cache::put("ai-dispatch-{$this->goat->id}", true, now()->addMinutes(2));

        $this->post(route('goats.ai-insights.store', $this->goat))
            ->assertOk()
            ->assertJson(['status' => 'debounced']);

        Queue::assertNotPushed(RunAiAnalysis::class);
    }

    public function test_history_endpoint_lists_analyses_newest_first(): void
    {
        $this->makeAnalysis(['generated_at' => now()->subDays(2), 'model' => 'old']);
        $this->makeAnalysis(['generated_at' => now()->subDays(1), 'model' => 'mid']);
        $this->makeAnalysis(['model' => 'new']);

        $response = $this->get(route('goats.ai-insights.index', $this->goat))
            ->assertOk()
            ->assertJsonStructure(['analyses']);

        $models = collect($response->json('analyses'))->pluck('model');
        $this->assertSame(['new', 'mid', 'old'], $models->all());
        $this->assertArrayHasKey('severity_summary', $response->json('analyses.0'));
    }

    public function test_dispatch_not_debounced_for_old_analysis(): void
    {
        Queue::fake();
        $this->makeAnalysis(['generated_at' => now()->subHour()]);

        $this->post(route('goats.ai-insights.store', $this->goat))->assertOk();

        Queue::assertPushed(RunAiAnalysis::class);
    }

    public function test_latest_endpoint_returns_pending_when_none(): void
    {
        $this->get(route('goats.ai-insights.latest', $this->goat))
            ->assertOk()
            ->assertJson(['status' => 'pending']);
    }

    public function test_latest_endpoint_returns_stored_analysis(): void
    {
        $analysis = $this->makeAnalysis();

        $response = $this->get(route('goats.ai-insights.latest', $this->goat))
            ->assertOk()
            ->assertJson(['status' => 'ready']);

        $this->assertSame('Weight trend healthy', $response->json('insights.0.title'));
        $this->assertSame('warning', $response->json('insights.1.severity'));
        $this->assertSame('Schedule CDT booster', $response->json('suggestions.0.title'));
    }

    public function test_goat_page_renders_insights_section_with_disclaimer_for_existing_analysis(): void
    {
        $this->makeAnalysis();

        $response = $this->get(route('goats.show', $this->goat));

        $response->assertStatus(200);
        $response->assertSee('AI Health Insights');
        $response->assertSee('Generate AI Insights');
    }

    public function test_goat_page_renders_for_goat_without_analysis(): void
    {
        $response = $this->get(route('goats.show', $this->goat));

        $response->assertStatus(200);
        $response->assertSee('AI Health Insights');
    }

    public function test_latest_analysis_only_returned_when_multiple_exist(): void
    {
        $this->makeAnalysis(['generated_at' => now()->subDay(), 'model' => 'old-model']);
        $this->makeAnalysis(['model' => 'new-model']);

        $response = $this->get(route('goats.ai-insights.latest', $this->goat));

        $this->assertSame('new-model', $response->json('model'));
    }
}
