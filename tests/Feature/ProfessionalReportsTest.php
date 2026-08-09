<?php

namespace Tests\Feature;

use App\Models\Investor;
use App\Models\ProfitDistribution;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfessionalReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function profit_loss_page_returns_all_chart_data_keys(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/reports/profit-loss');

        $response->assertStatus(200);
        $response->assertViewHas('chartData');

        $chartData = $response->viewData('chartData');

        $requiredKeys = [
            'monthly_labels', 'monthly_income', 'monthly_expense', 'monthly_net',
            'expense_cat_labels', 'expense_cat_values',
            'income_cat_labels', 'income_cat_values',
            'daily_labels', 'daily_net',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $chartData, "Missing chart data key: {$key}");
        }
    }

    #[Test]
    public function profit_loss_page_has_kpi_cards_and_tabs(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/reports/profit-loss');

        $response->assertStatus(200);
        $response->assertSee('Total Income', false);
        $response->assertSee('Total Expense', false);
        $response->assertSee('Net', false);
        $response->assertSee('Margin', false);
        $response->assertSee('Graphs', false);
        $response->assertSee('Data', false);
        $response->assertSee('reportCharts()', false);
    }

    #[Test]
    public function category_filter_only_returns_matching_transactions(): void
    {
        Transaction::factory()->create(['type' => 'expense', 'category' => 'Feed & Fodder', 'amount' => 500, 'date' => now()]);
        Transaction::factory()->create(['type' => 'expense', 'category' => 'Veterinary & Medicine', 'amount' => 300, 'date' => now()]);

        $start = now()->startOfMonth()->format('Y-m-d');
        $end = now()->endOfMonth()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->call('GET', '/reports/profit-loss', [
                'start' => $start,
                'end' => $end,
                'category' => 'Feed & Fodder',
            ]);

        $response->assertStatus(200);

        $expense = $response->viewData('expense');
        $this->assertSame(1, $expense->count(), 'Should only see Feed & Fodder expense');
        $this->assertSame('Feed & Fodder', $expense->first()->category);
    }

    #[Test]
    public function type_filter_income_only(): void
    {
        Transaction::factory()->create(['type' => 'income', 'category' => 'Goat Sales', 'amount' => 5000, 'date' => now()]);
        Transaction::factory()->create(['type' => 'expense', 'category' => 'Feed & Fodder', 'amount' => 500, 'date' => now()]);

        $response = $this->actingAs($this->user)
            ->get('/reports/profit-loss?start=' . now()->startOfMonth()->format('Y-m-d') . '&end=' . now()->endOfMonth()->format('Y-m-d') . '&type=income');

        $response->assertStatus(200);

        $allTransactions = $response->viewData('allTransactions');
        $this->assertSame(1, $allTransactions->count());
        $this->assertSame('income', $allTransactions->first()->type);
    }

    #[Test]
    public function distributions_are_scoped_to_selected_period(): void
    {
        $investor = Investor::factory()->create();

        ProfitDistribution::factory()->create([
            'investor_id' => $investor->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'amount' => 1000,
        ]);
        ProfitDistribution::factory()->create([
            'investor_id' => $investor->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'amount' => 2000,
        ]);

        // Query for January period
        $response = $this->actingAs($this->user)
            ->get('/reports/profit-loss?start=2026-01-01&end=2026-01-31');

        $response->assertStatus(200);

        $distributions = $response->viewData('distributions');
        $this->assertSame(1, $distributions->count(), 'Should only see January distribution');
        $this->assertSame('1000.00', (string) $distributions->first()->amount);
    }

    #[Test]
    public function csv_export_returns_correct_content_type(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/reports/profit-loss/csv?start=2026-01-01&end=2026-01-31');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
    }

    #[Test]
    public function csv_export_contains_transactions(): void
    {
        Transaction::factory()->create([
            'type' => 'income', 'category' => 'Goat Sales', 'amount' => 5000,
            'date' => '2026-01-15',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/reports/profit-loss/csv?start=2026-01-01&end=2026-01-31');

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('Profit & Loss Report', $content);
        $this->assertStringContainsString('Goat Sales', $content);
        $this->assertStringContainsString('5,000.00', $content);
    }

    #[Test]
    public function pdf_export_returns_pdf_content_type(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/reports/profit-loss/pdf?start=2026-01-01&end=2026-01-31');

        $response->assertStatus(200);
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('application/pdf', $contentType);
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
    }

    #[Test]
    public function distribute_guarded_against_negative_profit(): void
    {
        // Create only expenses, no income
        Transaction::factory()->create([
            'type' => 'expense', 'category' => 'Feed & Fodder', 'amount' => 5000,
            'date' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->post('/reports/distribute', [
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end' => now()->endOfMonth()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function report_page_has_export_buttons(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/reports/profit-loss');

        $response->assertStatus(200);
        $response->assertSee('CSV', false);
        $response->assertSee('PDF', false);
        $response->assertSee('reports/profit-loss/csv', false);
        $response->assertSee('reports/profit-loss/pdf', false);
    }

    #[Test]
    public function monthly_chart_data_groups_correctly(): void
    {
        // Transaction in current month
        Transaction::factory()->create(['type' => 'income', 'amount' => 3000, 'date' => now()]);
        // Transaction in previous month
        Transaction::factory()->create(['type' => 'income', 'amount' => 2000, 'date' => now()->subMonth()]);

        $response = $this->actingAs($this->user)
            ->get('/reports/profit-loss?start=' . now()->subMonth()->startOfMonth()->format('Y-m-d') . '&end=' . now()->endOfMonth()->format('Y-m-d'));

        $chartData = $response->viewData('chartData');

        // Should have 2 months
        $this->assertCount(2, $chartData['monthly_labels']);
        // Total income across 2 months = 5000
        $this->assertSame(5000.0, array_sum($chartData['monthly_income']));
    }

    #[Test]
    public function daily_cashflow_chart_shows_days_with_activity(): void
    {
        Transaction::factory()->create(['type' => 'income', 'amount' => 1000, 'date' => '2026-01-10']);
        Transaction::factory()->create(['type' => 'expense', 'amount' => 500, 'date' => '2026-01-10']);
        Transaction::factory()->create(['type' => 'income', 'amount' => 2000, 'date' => '2026-01-15']);

        $response = $this->actingAs($this->user)
            ->get('/reports/profit-loss?start=2026-01-01&end=2026-01-31');

        $chartData = $response->viewData('chartData');

        // Should have 2 entries (Jan 10 and Jan 15)
        $this->assertCount(2, $chartData['daily_labels']);
        // Jan 10: 1000 - 500 = 500
        $this->assertSame(500.0, $chartData['daily_net'][0]);
        // Jan 15: 2000
        $this->assertSame(2000.0, $chartData['daily_net'][1]);
    }
}
