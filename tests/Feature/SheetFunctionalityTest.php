<?php

namespace Tests\Feature;

use App\Models\BudgetEntry;
use App\Models\Breed;
use App\Models\Goat;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SheetFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // ---- Investor Settlement Tests --------------------------------

    public function test_investor_has_role_and_expense_split_defaults(): void
    {
        $inv = Investor::create(['name' => 'Test Investor']);
        $inv = $inv->fresh();
        $this->assertEquals('investor', $inv->role);
        $this->assertEquals(33.33, (float) $inv->expense_split_pct);
    }

    public function test_investor_role_can_be_manager(): void
    {
        $inv = Investor::create(['name' => 'Manager', 'role' => 'manager']);
        $this->assertTrue($inv->isManager());
    }

    public function test_settlement_balance_calculates_correctly(): void
    {
        $inv = Investor::create(['name' => 'Test', 'role' => 'investor', 'expense_split_pct' => 50]);
        Investment::create(['investor_id' => $inv->id, 'amount' => 100000, 'date' => '2026-01-01']);

        // Create expenses totalling 1000 (no investor linked)
        Transaction::create(['type' => 'expense', 'category' => 'Feed', 'amount' => 1000, 'date' => '2026-06-01']);

        // Investor paid 300
        Transaction::create([
            'type' => 'expense', 'category' => 'Labor', 'amount' => 300,
            'date' => '2026-06-02', 'investor_id' => $inv->id,
        ]);

        // Total expenses = 1000 + 300 = 1300, split 50% = owed 650
        // Settlement = paid - owed = 300 - 650 = -350
        $this->assertEquals(650.0, $inv->expense_owed);
        $this->assertEquals(300.0, $inv->expense_paid);
        $this->assertEquals(-350.0, $inv->settlement_balance);
    }

    public function test_herd_share_and_profit_share_calculate_from_goats(): void
    {
        $breed = Breed::create(['name' => 'Test Breed']);
        $inv = Investor::create(['name' => 'Test', 'role' => 'investor']);
        Investment::create(['investor_id' => $inv->id, 'amount' => 200000, 'date' => '2026-01-01']);

        // In-herd goat worth 1000
        Goat::create([
            'tag_id' => 'T1', 'breed_id' => $breed->id, 'gender' => 'male',
            'status' => 'grower', 'estimated_value' => 1000,
        ]);

        // Investor is sole investor → 100% share → herd_share = 1000
        $this->assertEquals(1000.0, $inv->herd_share);
    }

    // ---- Goat Computed Attribute Tests ----------------------------

    public function test_goat_realized_gain_loss(): void
    {
        $breed = Breed::create(['name' => 'Test Breed']);
        $goat = Goat::create([
            'tag_id' => 'G1', 'breed_id' => $breed->id, 'gender' => 'male',
            'status' => 'sold', 'purchase_price' => 150, 'sale_price' => 300,
            'purchase_date' => '2026-01-01', 'sale_date' => '2026-06-01',
        ]);

        $this->assertEquals(150.0, $goat->realized_gain_loss);
    }

    public function test_goat_realized_gain_loss_zero_for_unsold(): void
    {
        $breed = Breed::create(['name' => 'Test Breed']);
        $goat = Goat::create([
            'tag_id' => 'G2', 'breed_id' => $breed->id, 'gender' => 'female',
            'status' => 'grower', 'purchase_price' => 150, 'sale_price' => 0,
        ]);

        $this->assertEquals(0, $goat->realized_gain_loss);
    }

    public function test_goat_days_held(): void
    {
        $breed = Breed::create(['name' => 'Test Breed']);
        $goat = Goat::create([
            'tag_id' => 'G3', 'breed_id' => $breed->id, 'gender' => 'male',
            'status' => 'sold', 'purchase_price' => 100, 'sale_price' => 200,
            'purchase_date' => '2026-01-01', 'sale_date' => '2026-02-01',
        ]);

        $this->assertEquals(31, $goat->days_held);
    }

    public function test_goat_current_value_for_in_herd(): void
    {
        $breed = Breed::create(['name' => 'Test Breed']);
        $goat = Goat::create([
            'tag_id' => 'G4', 'breed_id' => $breed->id, 'gender' => 'male',
            'status' => 'grower', 'estimated_value' => 250,
        ]);

        $this->assertEquals(250.0, $goat->current_value);
    }

    public function test_goat_current_value_for_deceased_is_zero(): void
    {
        $breed = Breed::create(['name' => 'Test Breed']);
        $goat = Goat::create([
            'tag_id' => 'G5', 'breed_id' => $breed->id, 'gender' => 'male',
            'status' => 'deceased', 'estimated_value' => 250,
        ]);

        $this->assertEquals(0, $goat->current_value);
    }

    // ---- Transaction Variance Tests -------------------------------

    public function test_transaction_variance(): void
    {
        $t = Transaction::create([
            'type' => 'expense', 'category' => 'Feed', 'amount' => 150,
            'date' => '2026-06-01', 'budget_amount' => 200,
        ]);

        $this->assertEquals(50.0, $t->variance);
    }

    public function test_transaction_variance_null_without_budget(): void
    {
        $t = Transaction::create([
            'type' => 'expense', 'category' => 'Feed', 'amount' => 150,
            'date' => '2026-06-01',
        ]);

        $this->assertNull($t->variance);
    }

    public function test_transaction_new_fields_defaults(): void
    {
        $t = Transaction::create([
            'type' => 'expense', 'category' => 'Feed', 'amount' => 100,
            'date' => '2026-06-01',
        ]);
        $t = $t->fresh();

        $this->assertEquals('cash', $t->payment_method);
        $this->assertFalse($t->is_recurring);
        $this->assertNull($t->budget_amount);
    }

    // ---- Budget Entry Tests ---------------------------------------

    public function test_budget_entry_annual_total(): void
    {
        $entry = BudgetEntry::create([
            'type' => 'expense', 'category' => 'Feed & Fodder', 'year' => 2026,
            'jan' => 100, 'feb' => 100, 'mar' => 100,
            'apr' => 100, 'may' => 100, 'jun' => 100,
            'jul' => 100, 'aug' => 100, 'sep' => 100,
            'oct' => 100, 'nov' => 100, 'dec' => 100,
        ]);

        $this->assertEquals(1200.0, $entry->annual_total);
    }

    public function test_budget_entry_can_be_created_via_form(): void
    {
        $response = $this->post('/budget', [
            'type' => 'expense', 'category' => 'Test Category', 'year' => 2026,
            'jan' => 50, 'feb' => 0, 'mar' => 0, 'apr' => 0, 'may' => 0, 'jun' => 0,
            'jul' => 0, 'aug' => 0, 'sep' => 0, 'oct' => 0, 'nov' => 0, 'dec' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('budget_entries', [
            'category' => 'Test Category', 'jan' => 50,
        ]);
    }

    public function test_budget_seed_creates_all_categories(): void
    {
        $response = $this->post('/budget/seed', ['year' => 2026]);

        $response->assertRedirect();
        // 4 income + 11 expense = 15 categories
        $this->assertEquals(15, BudgetEntry::where('year', 2026)->count());
    }

    public function test_budget_index_displays(): void
    {
        BudgetEntry::create(['type' => 'income', 'category' => 'Goat Sales', 'year' => 2026, 'jan' => 500]);

        $response = $this->get('/budget?year=2026');

        $response->assertStatus(200);
        $response->assertSee('Goat Sales');
        $response->assertSee('500');
    }

    public function test_budget_inline_update(): void
    {
        $entry = BudgetEntry::create(['type' => 'expense', 'category' => 'Feed', 'year' => 2026, 'jan' => 0]);

        $response = $this->post("/budget/{$entry->id}/inline", [
            'month' => 'jan', 'value' => 250,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals(250, (float) $entry->fresh()->jan);
    }

    public function test_budget_delete(): void
    {
        $entry = BudgetEntry::create(['type' => 'income', 'category' => 'Delete Me', 'year' => 2026]);

        $response = $this->delete("/budget/{$entry->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('budget_entries', ['id' => $entry->id]);
    }

    // ---- Dashboard Tests ------------------------------------------

    public function test_dashboard_shows_financial_kpis(): void
    {
        $breed = Breed::create(['name' => 'Boer']);
        $inv = Investor::create(['name' => 'Test Inv']);
        Investment::create(['investor_id' => $inv->id, 'amount' => 500000, 'date' => '2026-01-01']);

        Goat::create([
            'tag_id' => 'D1', 'breed_id' => $breed->id, 'gender' => 'male',
            'status' => 'grower', 'estimated_value' => 300,
        ]);
        Goat::create([
            'tag_id' => 'D2', 'breed_id' => $breed->id, 'gender' => 'female',
            'status' => 'sold', 'purchase_price' => 200, 'sale_price' => 350,
        ]);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Total Capital');
        $response->assertSee('Herd Value');
        $response->assertSee('Realized Profit');
        $response->assertSee('Net Profit');
        $response->assertSee('Return %');
        $response->assertSee('500,000');
    }

    public function test_dashboard_shows_investor_positions(): void
    {
        $inv = Investor::create(['name' => 'Position Test', 'role' => 'manager']);
        Investment::create(['investor_id' => $inv->id, 'amount' => 100000, 'date' => '2026-01-01']);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Position Test');
        $response->assertSee('Manager');
        $response->assertSee('Settlement');
    }

    public function test_dashboard_shows_expense_breakdown(): void
    {
        Transaction::create(['type' => 'expense', 'category' => 'Feed & Fodder', 'amount' => 500, 'date' => '2026-06-01']);
        Transaction::create(['type' => 'expense', 'category' => 'Veterinary & Medicine', 'amount' => 200, 'date' => '2026-06-02']);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Expenses by Category');
        $response->assertSee('Feed & Fodder');
        $response->assertSee('Veterinary & Medicine');
    }

    // ---- Livestock Summary Tests ----------------------------------

    public function test_goats_index_shows_herd_summary(): void
    {
        $breed = Breed::create(['name' => 'Test']);
        Goat::create(['tag_id' => 'H1', 'breed_id' => $breed->id, 'gender' => 'male', 'status' => 'grower', 'estimated_value' => 200]);
        Goat::create(['tag_id' => 'H2', 'breed_id' => $breed->id, 'gender' => 'female', 'status' => 'sold', 'purchase_price' => 100, 'sale_price' => 250]);
        Goat::create(['tag_id' => 'H3', 'breed_id' => $breed->id, 'gender' => 'male', 'status' => 'deceased']);

        $response = $this->get('/goats');

        $response->assertStatus(200);
        $response->assertSee('Total Purchased');
        $response->assertSee('In Herd');
        $response->assertSee('Mortality Rate');
    }

    // ---- Bulk Delete Tests ----------------------------------------

    public function test_bulk_delete_removes_multiple_goats(): void
    {
        $breed = Breed::create(['name' => 'Test']);
        $g1 = Goat::create(['tag_id' => 'B1', 'breed_id' => $breed->id, 'gender' => 'male', 'status' => 'grower']);
        $g2 = Goat::create(['tag_id' => 'B2', 'breed_id' => $breed->id, 'gender' => 'female', 'status' => 'grower']);
        $g3 = Goat::create(['tag_id' => 'B3', 'breed_id' => $breed->id, 'gender' => 'male', 'status' => 'grower']);

        $response = $this->delete('/goats/bulk', [
            'ids' => [$g1->id, $g3->id],
        ]);

        $response->assertRedirect('/goats');
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('goats', ['id' => $g1->id]);
        $this->assertSoftDeleted('goats', ['id' => $g3->id]);
        $this->assertDatabaseHas('goats', ['id' => $g2->id, 'deleted_at' => null]);
    }

    public function test_bulk_delete_requires_at_least_one_id(): void
    {
        $response = $this->delete('/goats/bulk', ['ids' => []]);

        $response->assertSessionHasErrors('ids');
    }

    public function test_bulk_delete_rejects_nonexistent_ids(): void
    {
        $response = $this->delete('/goats/bulk', ['ids' => [999999]]);

        $response->assertSessionHasErrors('ids.0');
    }

    // ---- Instructions Page Tests ----------------------------------

    public function test_instructions_page_displays(): void
    {
        $response = $this->get('/instructions');

        $response->assertStatus(200);
        $response->assertSee('Instructions');
        $response->assertSee('Investors');
        $response->assertSee('Livestock');
        $response->assertSee('Budget Planner');
    }

    // ---- Transaction Show Page Tests ------------------------------

    public function test_transaction_show_displays_all_fields(): void
    {
        $inv = Investor::create(['name' => 'Payer']);
        $t = Transaction::create([
            'type' => 'expense', 'category' => 'Feed & Fodder', 'amount' => 150,
            'date' => '2026-06-01', 'investor_id' => $inv->id,
            'payment_method' => 'bank_transfer', 'is_recurring' => true,
            'budget_amount' => 200, 'notes' => 'Monthly feed',
        ]);

        $response = $this->get("/transactions/{$t->id}");

        $response->assertStatus(200);
        $response->assertSee('Feed & Fodder');
        $response->assertSee('Bank transfer');
        $response->assertSee('Yes');
        $response->assertSee('Monthly feed');
    }
}
