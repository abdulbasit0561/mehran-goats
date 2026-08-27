<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\Investor;
use App\Models\Transaction;
use App\Models\User;
use App\Models\YearlyClearance;
use App\Services\YearlyClearanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YearlyClearanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Two partners: Ali 60% capital, Sara 40% capital
        $this->ali  = Investor::create(['name' => 'Ali Raza', 'role' => 'investor', 'expense_split_pct' => 0]);
        $this->sara = Investor::create(['name' => 'Sara Khan', 'role' => 'manager', 'expense_split_pct' => 0]);

        // 2025: capital base
        Investment::create(['investor_id' => $this->ali->id,  'amount' => 6000, 'date' => '2025-01-05', 'notes' => 'seed capital']);
        Investment::create(['investor_id' => $this->sara->id, 'amount' => 4000, 'date' => '2025-01-05', 'notes' => 'seed capital']);

        // 2025 income
        Transaction::create(['type' => 'income', 'category' => 'Goat Sales', 'amount' => 5000, 'date' => '2025-06-15']);
        // 2025 expenses: 1000 purchases + 500 operating
        Transaction::create(['type' => 'expense', 'category' => 'Goat Purchases', 'amount' => 1000, 'date' => '2025-03-01']);
        Transaction::create(['type' => 'expense', 'category' => 'Feed & Fodder', 'amount' => 500, 'date' => '2025-07-01']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        auth()->logout();

        $this->get('/reports/yearly-clearance')->assertRedirect('/login');
        $this->get('/reports/yearly-clearance/report?year=2025')->assertRedirect('/login');
        $this->post('/reports/yearly-clearance/complete', ['year' => 2025])->assertRedirect('/login');
    }

    public function test_report_page_renders_totals_and_partner_settlement(): void
    {
        // 2026 activity
        Investment::create(['investor_id' => $this->ali->id, 'amount' => 1000, 'date' => '2026-02-01']);
        Transaction::create(['type' => 'income', 'category' => 'Goat Sales', 'amount' => 12000, 'date' => '2026-05-01']);
        Transaction::create(['type' => 'expense', 'category' => 'Goat Purchases', 'amount' => 3000, 'date' => '2026-03-01']);
        Transaction::create(['type' => 'expense', 'category' => 'Labor / Wages', 'amount' => 1000, 'date' => '2026-06-01', 'investor_id' => $this->sara->id]);
        Transaction::create(['type' => 'income', 'category' => 'Other Income', 'amount' => 800, 'date' => '2026-07-01', 'investor_id' => $this->ali->id]);

        $response = $this->get('/reports/yearly-clearance/report?year=2026');

        $response->assertStatus(200);
        $response->assertSee('12,800');   // total sales (12000 + 800 partner withdrawal)
        $response->assertSee('3,000');    // total purchases
        $response->assertSee('4,000');    // total expenses (3000 + 1000)
        $response->assertSee('Ali Raza');
        $response->assertSee('Sara Khan');
    }

    public function test_complete_creates_clearance_with_correct_totals(): void
    {
        $response = $this->post('/reports/yearly-clearance/complete', ['year' => 2025]);

        $response->assertRedirect();
        $this->assertDatabaseHas('yearly_clearances', [
            'year'            => 2025,
            'total_sales'     => 5000,
            'total_purchases' => 1000,
            'total_expenses'  => 1500,
            'gross_profit'    => 4000,
            'net_profit'      => 3500,
        ]);
        $this->assertDatabaseCount('yearly_clearance_partners', 2);

        // Profit share: Ali 60% of 3500 = 2100, Sara 40% = 1400
        $this->assertDatabaseHas('yearly_clearance_partners', [
            'investor_id' => $this->ali->id,
            'share_pct'   => 60,
            'profit_share' => 2100,
        ]);
        $this->assertDatabaseHas('yearly_clearance_partners', [
            'investor_id' => $this->sara->id,
            'share_pct'   => 40,
            'profit_share' => 1400,
        ]);
    }

    public function test_partner_final_balance_and_status_math(): void
    {
        // 2026: Sara pays expenses (300), Ali receives withdrawal (200)
        Transaction::create(['type' => 'expense', 'category' => 'Feed & Fodder', 'amount' => 300, 'date' => '2026-02-01', 'investor_id' => $this->sara->id]);
        Transaction::create(['type' => 'income', 'category' => 'Other Income', 'amount' => 200, 'date' => '2026-04-01', 'investor_id' => $this->ali->id]);
        // 2026 profit of 1000 for share math: income 1000, no expense
        Transaction::create(['type' => 'income', 'category' => 'Goat Sales', 'amount' => 1000, 'date' => '2026-08-01']);

        $this->post('/reports/yearly-clearance/complete', ['year' => 2026]);

        // 2026 net = 1000 + 200 - 300 = 900; shares stay Ali 60% / Sara 40% (capital unchanged)
        $report = app(YearlyClearanceService::class)->buildReport(2026);
        $this->assertEqualsWithDelta(900.0, $report['totals']['net_profit'], 0.01);

        // Opening fallback (no 2025 clearance): capital before year + share of pre-year profit
        // Ali: 6000 + 3500*0.6 = 8100 ; Sara: 4000 + 3500*0.4 = 5400
        $aliLine = collect($report['partners'])->firstWhere('investor_id', $this->ali->id);
        $this->assertEqualsWithDelta(8100.0, (float) $aliLine['opening_balance'], 0.01);
        // final = 8100 + 0 investment + 900*0.6 profit share - 200 withdrawal
        $this->assertEqualsWithDelta(8100 + 540 - 200, (float) $aliLine['final_balance'], 0.02);
        $this->assertEquals('payable_to_partner', $aliLine['status']);

        $saraLine = collect($report['partners'])->firstWhere('investor_id', $this->sara->id);
        $this->assertEqualsWithDelta(5400.0, (float) $saraLine['opening_balance'], 0.01);
        // final = 5400 + 0 + 900*0.4 + 300 expenses paid
        $this->assertEqualsWithDelta(5400 + 360 + 300, (float) $saraLine['final_balance'], 0.02);
        $this->assertEquals('payable_to_partner', $saraLine['status']);
    }

    public function test_opening_balance_chains_from_previous_year_clearance(): void
    {
        // Complete 2025 first: Ali final = 6000 capital + 2100 profit share, Sara = 4000 + 1400
        $this->post('/reports/yearly-clearance/complete', ['year' => 2025]);

        // 2026 data
        Transaction::create(['type' => 'income', 'category' => 'Goat Sales', 'amount' => 900, 'date' => '2026-08-01']);

        $report = app(YearlyClearanceService::class)->buildReport(2026);

        $aliLine = collect($report['partners'])->firstWhere('investor_id', $this->ali->id);
        $this->assertEqualsWithDelta(8100.0, (float) $aliLine['opening_balance'], 0.01);
        $saraLine = collect($report['partners'])->firstWhere('investor_id', $this->sara->id);
        $this->assertEqualsWithDelta(5400.0, (float) $saraLine['opening_balance'], 0.01);
    }

    public function test_completing_year_twice_is_blocked(): void
    {
        $this->post('/reports/yearly-clearance/complete', ['year' => 2025]);
        $response = $this->post('/reports/yearly-clearance/complete', ['year' => 2025]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('yearly_clearances', 1);
    }

    public function test_source_transactions_are_not_modified(): void
    {
        // 2026 transaction to complete against
        Transaction::create(['type' => 'income', 'category' => 'Goat Sales', 'amount' => 777, 'date' => '2026-01-15']);

        $before = Transaction::orderBy('id')->get()->map(fn ($t) => ['id' => $t->id, 'amount' => $t->amount, 'date' => $t->date?->toDateString(), 'type' => $t->type, 'category' => $t->category, 'investor_id' => $t->investor_id])->all();
        $beforeInvestments = Investment::orderBy('id')->get()->map(fn ($i) => ['id' => $i->id, 'amount' => $i->amount, 'date' => $i->date?->toDateString(), 'investor_id' => $i->investor_id])->all();

        $this->post('/reports/yearly-clearance/complete', ['year' => 2026]);

        $after = Transaction::orderBy('id')->get()->map(fn ($t) => ['id' => $t->id, 'amount' => $t->amount, 'date' => $t->date?->toDateString(), 'type' => $t->type, 'category' => $t->category, 'investor_id' => $t->investor_id])->all();
        $afterInvestments = Investment::orderBy('id')->get()->map(fn ($i) => ['id' => $i->id, 'amount' => $i->amount, 'date' => $i->date?->toDateString(), 'investor_id' => $i->investor_id])->all();

        $this->assertSame($before, $after);
        $this->assertSame($beforeInvestments, $afterInvestments);
    }

    public function test_completion_records_user_datetime_and_report_payload(): void
    {
        $this->travelTo(now()->setDate(2026, 8, 27)->setTime(10, 0, 0));
        $this->post('/reports/yearly-clearance/complete', ['year' => 2025]);

        $clearance = YearlyClearance::where('year', 2025)->first();
        $this->assertNotNull($clearance);
        $this->assertEquals($this->user->id, $clearance->completed_by);
        $this->assertEquals('2026-08-27 10:00:00', $clearance->completed_at->format('Y-m-d H:i:s'));
        $this->assertIsArray($clearance->report_payload);
        $this->assertArrayHasKey('totals', $clearance->report_payload);
        $this->assertArrayHasKey('partners', $clearance->report_payload);
    }

    public function test_report_available_with_zero_data(): void
    {
        $response = $this->get('/reports/yearly-clearance/report?year=2021');

        $response->assertStatus(200);
        $response->assertSee('0');
    }

    public function test_year_validation_rejects_invalid_years(): void
    {
        $tooOld = $this->post('/reports/yearly-clearance/complete', ['year' => 1999]);
        $tooOld->assertSessionHasErrors('year');

        $tooNew = $this->post('/reports/yearly-clearance/complete', ['year' => 2050]);
        $tooNew->assertSessionHasErrors('year');
    }

    public function test_loss_year_produces_negative_profit_share(): void
    {
        // 2026 loss: heavy expenses, no income
        Transaction::create(['type' => 'expense', 'category' => 'Feed & Fodder', 'amount' => 2500, 'date' => '2026-05-01']);

        $this->post('/reports/yearly-clearance/complete', ['year' => 2026]);

        // net = 0 income − 2500 expense = −2500; Ali 60% → −1500
        $this->assertDatabaseHas('yearly_clearances', ['year' => 2026, 'net_profit' => -2500]);
        $this->assertDatabaseHas('yearly_clearance_partners', ['investor_id' => $this->ali->id, 'profit_share' => -1500]);
    }

    public function test_activity_log_recorded_for_clearance_creation(): void
    {
        $this->post('/reports/yearly-clearance/complete', ['year' => 2025]);

        $this->assertDatabaseHas('activity_logs', [
            'entity_type' => 'App\Models\YearlyClearance',
            'action'      => 'create',
        ]);
    }

    public function test_index_lists_completed_clearances(): void
    {
        $this->post('/reports/yearly-clearance/complete', ['year' => 2025]);

        $response = $this->get('/reports/yearly-clearance');
        $response->assertStatus(200);
        $response->assertSee('2025');
    }

    public function test_show_displays_completed_entry(): void
    {
        $this->post('/reports/yearly-clearance/complete', ['year' => 2025]);
        $clearance = YearlyClearance::where('year', 2025)->first();

        $response = $this->get("/reports/yearly-clearance/{$clearance->id}");
        $response->assertStatus(200);
        $response->assertSee('Ali Raza');
        $response->assertSee('FY 2025');
    }

    public function test_csv_export_contains_partner_data(): void
    {
        $this->post('/reports/yearly-clearance/complete', ['year' => 2025]);
        $clearance = YearlyClearance::where('year', 2025)->first();

        $response = $this->get("/reports/yearly-clearance/{$clearance->id}/csv");
        $response->assertStatus(200);

        $content = $response->streamedContent();
        $this->assertStringContainsString('Ali Raza', $content);
        $this->assertStringContainsString('Yearly Clearance Report', $content);
    }

    public function test_pdf_export_works(): void
    {
        $this->post('/reports/yearly-clearance/complete', ['year' => 2025]);
        $clearance = YearlyClearance::where('year', 2025)->first();

        $response = $this->get("/reports/yearly-clearance/{$clearance->id}/pdf");
        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }
}

