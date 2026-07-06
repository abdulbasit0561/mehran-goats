<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\Investor;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionAndProfitLossTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_an_income_transaction(): void
    {
        $response = $this->post('/transactions', [
            'type'     => 'income',
            'category' => 'Sale',
            'amount'   => 500,
            'date'     => '2026-06-01',
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', ['amount' => 500, 'type' => 'income']);
    }

    public function test_amount_must_be_positive(): void
    {
        $response = $this->post('/transactions', [
            'type'     => 'income',
            'category' => 'Sale',
            'amount'   => 0,
            'date'     => '2026-06-01',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_can_delete_a_transaction(): void
    {
        $transaction = Transaction::create([
            'type'     => 'expense',
            'category' => 'Feed',
            'amount'   => 200,
            'date'     => '2026-06-01',
        ]);

        $this->delete("/transactions/{$transaction->id}");

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_can_list_transactions(): void
    {
        Transaction::create(['type' => 'income', 'category' => 'Sale', 'amount' => 500, 'date' => '2026-06-01']);
        Transaction::create(['type' => 'expense', 'category' => 'Feed', 'amount' => 200, 'date' => '2026-06-02']);

        $response = $this->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('Sale');
        $response->assertSee('Feed');
    }

    public function test_can_filter_transactions_by_type(): void
    {
        Transaction::create(['type' => 'income', 'category' => 'Sale', 'amount' => 500, 'date' => '2026-06-01']);
        Transaction::create(['type' => 'expense', 'category' => 'Feed', 'amount' => 200, 'date' => '2026-06-02']);

        // Filter by income type — should return 1 transaction
        $response = $this->get('/transactions?type=income');

        $response->assertStatus(200);
        $response->assertSee('Sale');

        // The expense row should not appear in the table body
        $html = $response->getContent();
        $this->assertStringContainsString('Sale', $html);
        $this->assertStringNotContainsString('-200.00', $html, 'Expense transaction should not appear when filtering income');
    }

    public function test_profit_loss_report_shows_correct_totals(): void
    {
        Transaction::create(['type' => 'income', 'category' => 'Sale', 'amount' => 1000, 'date' => '2026-06-01']);
        Transaction::create(['type' => 'expense', 'category' => 'Feed', 'amount' => 300, 'date' => '2026-06-02']);

        $response = $this->get('/reports/profit-loss?start=2026-06-01&end=2026-06-30');

        $response->assertStatus(200);
        $response->assertSee('1,000.00');  // income
        $response->assertSee('300.00');     // expense
        $response->assertSee('700.00');     // net
    }

    public function test_investor_share_pct_calculation(): void
    {
        $a = Investor::create(['name' => 'Alice']);
        $b = Investor::create(['name' => 'Bob']);

        Investment::create(['investor_id' => $a->id, 'amount' => 600, 'date' => '2026-01-01']);
        Investment::create(['investor_id' => $b->id, 'amount' => 400, 'date' => '2026-01-01']);

        // Alice: 600/1000 = 60%
        $this->assertEquals(60.0, $a->fresh()->share_pct);
        $this->assertEquals(40.0, $b->fresh()->share_pct);
    }

    public function test_share_pct_returns_zero_with_no_investments(): void
    {
        $investor = Investor::create(['name' => 'Nobody']);

        $this->assertEquals(0.0, $investor->share_pct);
    }

    public function test_profit_distribution_creates_correct_amounts(): void
    {
        $a = Investor::create(['name' => 'Alice']);
        $b = Investor::create(['name' => 'Bob']);

        Investment::create(['investor_id' => $a->id, 'amount' => 750, 'date' => '2026-01-01']);
        Investment::create(['investor_id' => $b->id, 'amount' => 250, 'date' => '2026-01-01']);

        // Farm made 1000 profit
        Transaction::create(['type' => 'income', 'category' => 'Sale', 'amount' => 2000, 'date' => '2026-06-01']);
        Transaction::create(['type' => 'expense', 'category' => 'Feed', 'amount' => 1000, 'date' => '2026-06-02']);

        $response = $this->post('/reports/distribute', [
            'start' => '2026-06-01',
            'end'   => '2026-06-30',
        ]);

        $response->assertRedirect('/reports/profit-loss');

        // Alice gets 75% of 1000 = 750, Bob gets 25% = 250
        $this->assertDatabaseHas('profit_distributions', [
            'investor_id' => $a->id,
            'amount'      => 750,
            'status'      => 'pending',
        ]);
        $this->assertDatabaseHas('profit_distributions', [
            'investor_id' => $b->id,
            'amount'      => 250,
        ]);
    }

    public function test_distribution_is_idempotent(): void
    {
        $a = Investor::create(['name' => 'Alice']);
        Investment::create(['investor_id' => $a->id, 'amount' => 1000, 'date' => '2026-01-01']);
        Transaction::create(['type' => 'income', 'category' => 'Sale', 'amount' => 1000, 'date' => '2026-06-01']);

        // Distribute once
        $this->post('/reports/distribute', ['start' => '2026-06-01', 'end' => '2026-06-30']);
        $count1 = \App\Models\ProfitDistribution::where('investor_id', $a->id)->count();
        $this->assertEquals(1, $count1);

        // Distribute again for same period — should NOT create duplicates
        $this->post('/reports/distribute', ['start' => '2026-06-01', 'end' => '2026-06-30']);
        $count2 = \App\Models\ProfitDistribution::where('investor_id', $a->id)->count();
        $this->assertEquals(1, $count2, 'Duplicate distribution created for same period');
    }
}
