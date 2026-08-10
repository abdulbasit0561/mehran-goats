<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSummaryFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** Seed helper: create expense + income across multiple months/categories */
    private function seedTransactions(): void
    {
        Transaction::create(['type' => 'expense', 'category' => 'Feed & Fodder',           'amount' => 500, 'date' => '2026-04-01']);
        Transaction::create(['type' => 'expense', 'category' => 'Feed & Fodder',           'amount' => 300, 'date' => '2026-05-01']);
        Transaction::create(['type' => 'expense', 'category' => 'Veterinary & Medicine',   'amount' => 200, 'date' => '2026-05-15']);
        Transaction::create(['type' => 'expense', 'category' => 'Labor / Wages',           'amount' => 400, 'date' => '2026-06-01']);
        Transaction::create(['type' => 'income',  'category' => 'Goat Sales',              'amount' => 1000, 'date' => '2026-05-20']);
        Transaction::create(['type' => 'income',  'category' => 'Milk Sales',              'amount' => 500, 'date' => '2026-06-10']);
    }

    public function test_summary_shows_all_time_totals_with_no_filter(): void
    {
        $this->seedTransactions();

        $response = $this->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee(number_format(1400, 0)); // total expense
        $response->assertSee(number_format(1500, 0)); // total income
    }

    public function test_summary_respects_date_range_filter(): void
    {
        $this->seedTransactions();

        // Filter to May 2026 only
        $response = $this->get('/transactions?from=2026-05-01&to=2026-05-31');

        $response->assertStatus(200);
        // May expenses: 300 (Feed) + 200 (Vet) = 500
        $response->assertSee(number_format(500, 0));
        // May income: 1000 (Goat Sales)
        $response->assertSee(number_format(1000, 0));
    }

    public function test_summary_respects_type_filter(): void
    {
        $this->seedTransactions();

        $response = $this->get('/transactions?type=expense');

        $response->assertStatus(200);
        // Should show full expense total
        $response->assertSee(number_format(1400, 0));
        // Income should be 0 in the filtered set
        $response->assertSee(number_format(0, 0));
    }

    public function test_summary_respects_category_filter(): void
    {
        $this->seedTransactions();

        $response = $this->get('/transactions?category=Feed+%26+Fodder');

        $response->assertStatus(200);
        // Only Feed & Fodder expenses: 500 + 300 = 800
        $response->assertSee(number_format(800, 0));
    }
}
