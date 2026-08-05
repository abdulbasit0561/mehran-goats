<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\Goat;
use App\Models\Investor;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function dashboard_returns_all_required_chart_data_keys(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('chartData');

        $chartData = $response->viewData('chartData');

        $requiredKeys = [
            'monthly_labels', 'monthly_income', 'monthly_expense', 'monthly_net_profit',
            'expense_categories', 'expense_amounts',
            'breed_names', 'breed_counts',
            'goat_status',
            'investor_names', 'investor_capital',
            'breeding_status',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $chartData, "Missing chart data key: {$key}");
        }
    }

    #[Test]
    public function monthly_data_has_six_months(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $chartData = $response->viewData('chartData');

        $this->assertCount(6, $chartData['monthly_labels']);
        $this->assertCount(6, $chartData['monthly_income']);
        $this->assertCount(6, $chartData['monthly_expense']);
        $this->assertCount(6, $chartData['monthly_net_profit']);
    }

    #[Test]
    public function goat_status_counts_match_database(): void
    {
        $breed = Breed::factory()->create();
        // Status is enum('kid', 'grower', 'breeder', 'sold', 'deceased')
        Goat::factory()->count(3)->create(['status' => 'kid', 'breed_id' => $breed->id]);
        Goat::factory()->count(2)->create(['status' => 'sold', 'breed_id' => $breed->id]);
        Goat::factory()->create(['status' => 'deceased', 'breed_id' => $breed->id]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $chartData = $response->viewData('chartData');

        // 'in_herd' = all goats not sold/deceased (3 kids)
        $this->assertSame(3, $chartData['goat_status']['in_herd']);
        $this->assertSame(2, $chartData['goat_status']['sold']);
        $this->assertSame(1, $chartData['goat_status']['deceased']);
    }

    #[Test]
    public function breeding_status_counts_match_database(): void
    {
        $breed = Breed::factory()->create();
        $sire = Goat::factory()->create(['gender' => 'male', 'breed_id' => $breed->id]);
        $dam = Goat::factory()->create(['gender' => 'female', 'breed_id' => $breed->id]);

        BreedingRecord::factory()->create(['sire_id' => $sire->id, 'dam_id' => $dam->id, 'pregnancy_status' => 'bred']);
        BreedingRecord::factory()->create(['sire_id' => $sire->id, 'dam_id' => $dam->id, 'pregnancy_status' => 'pregnant']);
        BreedingRecord::factory()->count(2)->create(['sire_id' => $sire->id, 'dam_id' => $dam->id, 'pregnancy_status' => 'kidded']);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $chartData = $response->viewData('chartData');

        $this->assertSame(1, $chartData['breeding_status']['bred']);
        $this->assertSame(1, $chartData['breeding_status']['pregnant']);
        $this->assertSame(0, $chartData['breeding_status']['open']);
        $this->assertSame(2, $chartData['breeding_status']['kidded']);
    }

    #[Test]
    public function monthly_income_reflects_transactions(): void
    {
        $thisMonth = now();
        Transaction::factory()->create([
            'type' => 'income',
            'amount' => 5000,
            'date' => $thisMonth,
        ]);
        Transaction::factory()->create([
            'type' => 'expense',
            'amount' => 2000,
            'date' => $thisMonth,
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $chartData = $response->viewData('chartData');

        // Current month is the last index (index 5)
        $this->assertSame(5000.0, $chartData['monthly_income'][5]);
        $this->assertSame(2000.0, $chartData['monthly_expense'][5]);
        $this->assertSame(3000.0, $chartData['monthly_net_profit'][5]);
    }

    #[Test]
    public function chart_data_works_with_empty_database(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);

        $chartData = $response->viewData('chartData');

        $this->assertEmpty($chartData['expense_categories']);
        $this->assertEmpty($chartData['breed_names']);
        $this->assertSame(0, $chartData['goat_status']['in_herd']);
        $this->assertSame(0, $chartData['breeding_status']['bred']);
    }

    #[Test]
    public function dashboard_charts_tab_is_visible_in_html(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Charts', false);
        $response->assertSee('chartsDashboard()', false);
        $response->assertSee('chart.js', false);
        $response->assertSee("tab: 'overview'", false);
    }

    #[Test]
    public function investor_capital_distribution_is_correct(): void
    {
        $inv1 = Investor::factory()->create(['name' => 'Alice']);
        $inv2 = Investor::factory()->create(['name' => 'Bob']);

        $inv1->investments()->create(['amount' => 100000, 'date' => now()]);
        $inv2->investments()->create(['amount' => 50000, 'date' => now()]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $chartData = $response->viewData('chartData');

        $this->assertEquals(['Alice', 'Bob'], $chartData['investor_names']);
        $this->assertEquals([100000, 50000], $chartData['investor_capital']);
    }
}
