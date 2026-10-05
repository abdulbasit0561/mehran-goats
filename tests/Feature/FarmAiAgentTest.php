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

class FarmAiAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
        $this->goat = Goat::create([
            'tag_id'   => 'TAG-FARM-1',
            'breed_id' => $breed->id,
            'gender'   => 'female',
            'status'   => 'grower',
        ]);
        $this->actingAs($this->user);
    }

    private function makeFarmReport(array $overrides = []): AiAnalysis
    {
        return AiAnalysis::create(array_merge([
            'scope'           => 'farm',
            'goat_id'         => null,
            'insights'        => [['title' => 'Herd growing well', 'detail' => 'Weight gains across the herd', 'severity' => 'info']],
            'suggestions'     => [['title' => 'Increase fodder', 'detail' => 'Growing season demands more']],
            'alerts'          => [['title' => 'Watch goat X', 'detail' => 'Coughing reported', 'severity' => 'warning']],
            'recommendations' => [
                ['area' => 'feeding', 'title' => 'Raise protein', 'detail' => 'Add 10% protein supplement'],
                ['area' => 'breeding', 'title' => 'Plan mating', 'detail' => 'Two does ready'],
                ['area' => 'disease_prevention', 'title' => 'Deworm', 'detail' => 'Seasonal deworming due'],
            ],
            'model'           => 'test-model',
            'generated_at'    => now(),
        ], $overrides));
    }

    public function test_herd_report_command_stores_farm_analysis(): void
    {
        // Test through the job synchronously with a faked AI response
        $this->mock(\App\Services\Ai\AiHealthAdvisor::class, function ($mock) {
            $mock->shouldReceive('analyzeFarm')->andReturn([
                'insights'        => [['title' => 'Herd OK', 'detail' => 'Stable weights', 'severity' => 'info']],
                'suggestions'     => [['title' => 'Keep it up', 'detail' => 'Continue current plan']],
                'alerts'          => [],
                'recommendations' => [['area' => 'feeding', 'title' => 'T', 'detail' => 'D']],
            ]);
        });

        $this->artisan('ai:herd-report --fresh')->assertSuccessful();

        $this->assertDatabaseHas('ai_analyses', ['scope' => 'farm']);
    }

    public function test_run_now_endpoint_queues_farm_job(): void
    {
        Queue::fake();

        $this->post(route('farm.ai-insights.run'))
            ->assertOk()
            ->assertJson(['status' => 'started']);

        Queue::assertPushed(RunAiAnalysis::class, fn ($job) => $job->scope === 'farm');
    }

    public function test_run_now_debounces_fresh_report(): void
    {
        Queue::fake();
        $this->makeFarmReport(['generated_at' => now()->subMinute()]);

        $response = $this->post(route('farm.ai-insights.run'))
            ->assertOk()
            ->assertJson(['status' => 'debounced']);

        $this->assertSame('Herd growing well', $response->json('analysis.insights.0.title'));
        Queue::assertNotPushed(RunAiAnalysis::class);
    }

    public function test_latest_endpoint_returns_farm_report(): void
    {
        $this->makeFarmReport(['generated_at' => now()->subHour(), 'model' => 'old']);
        $this->makeFarmReport(['model' => 'new']);

        $response = $this->get(route('farm.ai-insights.latest'))
            ->assertOk()
            ->assertJson(['status' => 'ready']);

        $this->assertSame('new', $response->json('model'));
        $this->assertSame('feeding', $response->json('recommendations.0.area'));
    }

    public function test_ai_insights_page_renders_with_report_and_history(): void
    {
        $this->makeFarmReport(['generated_at' => now()->subWeek(), 'model' => 'week-old']);
        $this->makeFarmReport();

        $response = $this->get(route('farm.ai-insights.index'));

        $response->assertStatus(200);
        $response->assertSee('Weekly Herd Health Report');
        $response->assertSee('Previous weekly reports');
        $response->assertSee('Herd growing well');
    }

    public function test_ai_insights_page_renders_calm_state_without_report(): void
    {
        $response = $this->get(route('farm.ai-insights.index'));

        $response->assertStatus(200);
        $response->assertSee('No report yet');
        $response->assertSee('No active alerts');
    }

    public function test_dashboard_shows_ai_panel_with_latest_report(): void
    {
        $this->makeFarmReport();

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('AI Insights & Alerts');
        $response->assertSee('Watch goat X'); // AI alert surfaced
    }

    public function test_dashboard_shows_rule_based_alert(): void
    {
        // Goat with overdue vaccination triggers rule-based alert
        $this->goat->healthRecords()->create([
            'type' => 'vaccination', 'date' => now()->subMonths(14)->format('Y-m-d'), 'cost' => 50,
        ]);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Vaccination overdue — TAG-FARM-1');
    }

    public function test_endpoints_require_auth(): void
    {
        auth()->logout();

        $this->get(route('farm.ai-insights.index'))->assertRedirect('/login');
        $this->post(route('farm.ai-insights.run'))->assertRedirect('/login');
    }
}
