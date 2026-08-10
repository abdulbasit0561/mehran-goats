<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\Exchange;
use App\Models\Goat;
use App\Models\Investor;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
        $this->actingAs($this->user);

        // A goat in the herd to exchange
        $this->goat = Goat::create([
            'tag_id'         => 'OUT-001',
            'breed_id'       => $this->breed->id,
            'gender'         => 'male',
            'status'         => 'breeder',
            'purchase_price' => 40000,
            'birth_type'     => 'purchased',
        ]);
    }

    public function test_exchange_index_page_loads(): void
    {
        $response = $this->get('/exchanges');

        $response->assertStatus(200);
        $response->assertSee('Goat Exchanges');
    }

    public function test_exchange_create_form_loads_with_herd_goats(): void
    {
        $response = $this->get('/exchanges/create');

        $response->assertStatus(200);
        $response->assertSee('Record Goat Exchange');
        $response->assertSee('OUT-001');
    }

    public function test_can_record_exchange_where_you_pay_cash_difference(): void
    {
        $response = $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'INC-001',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_weight_kg' => 25,
            'incoming_value'     => 65000,
            'counterparty'       => 'Ahmed Khan',
            'payment_method'     => 'cash',
            'notes'              => 'Exchanged for a bigger doe',
        ]);

        $response->assertRedirect('/exchanges');
        $response->assertSessionHas('success');

        // Outgoing goat marked as sold
        $this->goat->refresh();
        $this->assertEquals('sold', $this->goat->status);
        $this->assertEquals(40000, (float) $this->goat->sale_price);

        // New incoming goat created
        $incoming = Goat::where('tag_id', 'INC-001')->first();
        $this->assertNotNull($incoming);
        $this->assertEquals(65000, (float) $incoming->purchase_price);
        $this->assertEquals('Ahmed Khan', $incoming->vendor);
        $this->assertEquals('purchased', $incoming->birth_type);

        // Exchange record created
        $exchange = Exchange::where('outgoing_goat_id', $this->goat->id)->first();
        $this->assertNotNull($exchange);
        $this->assertEquals('paid', $exchange->cash_direction);
        $this->assertEquals(25000, (float) $exchange->cash_amount);
        $this->assertEquals($incoming->id, $exchange->incoming_goat_id);

        // Income transaction for outgoing goat value
        $this->assertDatabaseHas('transactions', [
            'type'     => 'income',
            'category' => 'Goat Sales',
            'amount'   => 40000,
            'goat_id'  => $this->goat->id,
        ]);

        // Expense transaction for cash difference (Goat Purchases)
        $this->assertDatabaseHas('transactions', [
            'type'     => 'expense',
            'category' => 'Goat Purchases',
            'amount'   => 25000,
            'goat_id'  => $incoming->id,
        ]);
    }

    public function test_can_record_exchange_where_you_receive_cash_back(): void
    {
        // Your goat is worth 65,000, you get a goat worth 40,000
        $response = $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 65000,
            'incoming_tag_id'    => 'INC-002',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'male',
            'incoming_weight_kg' => 15,
            'incoming_value'     => 40000,
            'counterparty'       => 'Bilal',
            'payment_method'     => 'cash',
        ]);

        $response->assertRedirect('/exchanges');

        $exchange = Exchange::first();
        $this->assertEquals('received', $exchange->cash_direction);
        $this->assertEquals(25000, (float) $exchange->cash_amount);

        // Income transaction for cash received (Other Income)
        $this->assertDatabaseHas('transactions', [
            'type'     => 'income',
            'category' => 'Other Income',
            'amount'   => 25000,
        ]);

        // No Goat Purchases expense should exist
        $this->assertDatabaseMissing('transactions', [
            'type'     => 'expense',
            'category' => 'Goat Purchases',
        ]);
    }

    public function test_even_exchange_creates_no_cash_transactions(): void
    {
        $response = $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 50000,
            'incoming_tag_id'    => 'INC-003',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_weight_kg' => 20,
            'incoming_value'     => 50000,
            'payment_method'     => 'cash',
        ]);

        $response->assertRedirect('/exchanges');

        $exchange = Exchange::first();
        $this->assertEquals('none', $exchange->cash_direction);
        $this->assertEquals(0, (float) $exchange->cash_amount);

        // Only the Goat Sales income should exist, no cash diff transaction
        $this->assertEquals(1, Transaction::count());
        $this->assertDatabaseHas('transactions', [
            'type'     => 'income',
            'category' => 'Goat Sales',
            'amount'   => 50000,
        ]);
    }

    public function test_cannot_exchange_a_goat_that_is_already_sold(): void
    {
        $this->goat->update(['status' => 'sold']);

        $response = $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'INC-004',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 50000,
            'payment_method'     => 'cash',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, Exchange::count());
    }

    public function test_duplicate_incoming_tag_id_is_rejected(): void
    {
        // Create an existing goat with the tag we'll try to use
        Goat::create([
            'tag_id'   => 'TAKEN-TAG',
            'breed_id' => $this->breed->id,
            'gender'   => 'female',
            'status'   => 'breeder',
        ]);

        $response = $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'TAKEN-TAG',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 50000,
            'payment_method'     => 'cash',
        ]);

        $response->assertSessionHasErrors('incoming_tag_id');
        $this->assertEquals(0, Exchange::count());
    }

    public function test_exchange_date_cannot_be_in_future(): void
    {
        $future = now()->addDay()->format('Y-m-d');

        $response = $this->post('/exchanges', [
            'exchange_date'      => $future,
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'INC-005',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 50000,
            'payment_method'     => 'cash',
        ]);

        $response->assertSessionHasErrors('exchange_date');
    }

    public function test_show_page_displays_exchange_details(): void
    {
        $exchange = Exchange::create([
            'exchange_date'     => now()->format('Y-m-d'),
            'outgoing_goat_id'  => $this->goat->id,
            'outgoing_value'    => 40000,
            'incoming_goat_id'  => null,
            'incoming_tag_id'   => 'INC-006',
            'breed_id'          => $this->breed->id,
            'incoming_gender'   => 'female',
            'incoming_weight_kg'=> 20,
            'incoming_value'    => 65000,
            'counterparty'      => 'Ahmed',
            'cash_direction'    => 'paid',
            'cash_amount'       => 25000,
            'payment_method'    => 'cash',
        ]);

        $response = $this->get("/exchanges/{$exchange->id}");

        $response->assertStatus(200);
        $response->assertSee('Exchange Details');
        $response->assertSee('OUT-001');
        $response->assertSee('INC-006');
        $response->assertSee('Ahmed');
        $response->assertSee('Goat Purchases');
    }

    public function test_exchange_datatables_endpoint_returns_data(): void
    {
        Exchange::create([
            'exchange_date'     => now()->format('Y-m-d'),
            'outgoing_goat_id'  => $this->goat->id,
            'outgoing_value'    => 40000,
            'incoming_goat_id'  => null,
            'incoming_tag_id'   => 'INC-007',
            'breed_id'          => $this->breed->id,
            'incoming_gender'   => 'male',
            'incoming_value'    => 40000,
            'cash_direction'    => 'none',
            'cash_amount'       => 0,
            'payment_method'    => 'cash',
        ]);

        $response = $this->get('/exchanges/data');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.incoming_tag_id', 'INC-007');
    }

    public function test_investor_id_is_propagated_to_cash_difference_expense(): void
    {
        $investor = Investor::create([
            'name'            => 'Test Investor',
            'role'            => 'investor',
            'expense_split_pct' => 33.33,
        ]);

        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'INC-INV-1',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'investor_id'        => $investor->id,
            'payment_method'     => 'cash',
        ])->assertRedirect('/exchanges');

        // The cash difference expense should be tagged with the investor
        $this->assertDatabaseHas('transactions', [
            'type'        => 'expense',
            'category'    => 'Goat Purchases',
            'amount'      => 25000,
            'investor_id' => $investor->id,
        ]);

        // The Goat Sales income should also be tagged
        $this->assertDatabaseHas('transactions', [
            'type'        => 'income',
            'category'    => 'Goat Sales',
            'amount'      => 40000,
            'investor_id' => $investor->id,
        ]);

        // Exchange record should have the investor
        $this->assertDatabaseHas('exchanges', [
            'investor_id' => $investor->id,
        ]);
    }

    public function test_investor_id_is_propagated_to_cash_received_income(): void
    {
        $investor = Investor::create([
            'name'            => 'Test Investor 2',
            'role'            => 'investor',
            'expense_split_pct' => 33.33,
        ]);

        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 65000,
            'incoming_tag_id'    => 'INC-INV-2',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'male',
            'incoming_value'     => 40000,
            'investor_id'        => $investor->id,
            'payment_method'     => 'cash',
        ])->assertRedirect('/exchanges');

        // Cash received income tagged with investor
        $this->assertDatabaseHas('transactions', [
            'type'        => 'income',
            'category'    => 'Other Income',
            'amount'      => 25000,
            'investor_id' => $investor->id,
        ]);
    }

    public function test_exchange_without_investor_leaves_transactions_untagged(): void
    {
        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'INC-NO-INV',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'payment_method'     => 'cash',
        ])->assertRedirect('/exchanges');

        // Expense transaction should have null investor_id
        $this->assertDatabaseHas('transactions', [
            'type'        => 'expense',
            'category'    => 'Goat Purchases',
            'amount'      => 25000,
            'investor_id' => null,
        ]);
    }

    // ---- EDIT TESTS ----------------------------------------------------

    public function test_edit_form_loads_with_pre_filled_data(): void
    {
        // Create an exchange via POST so all linked records exist
        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'EDIT-001',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'counterparty'       => 'Ahmed',
            'payment_method'     => 'cash',
        ]);

        $exchange = Exchange::where('incoming_tag_id', 'EDIT-001')->first();

        $response = $this->get("/exchanges/{$exchange->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Edit Exchange');
        $response->assertSee('EDIT-001');
        $response->assertSee('40000');
        $response->assertSee('65000');
        $response->assertSee('Ahmed');
    }

    public function test_update_changes_values_and_syncs_transactions(): void
    {
        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'EDIT-002',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'counterparty'       => 'Original Party',
            'payment_method'     => 'cash',
        ]);

        $exchange = Exchange::where('incoming_tag_id', 'EDIT-002')->first();

        // Now update: change values
        $response = $this->put("/exchanges/{$exchange->id}", [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_value'     => 50000,
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'male',
            'incoming_value'     => 70000,
            'counterparty'       => 'Updated Party',
            'payment_method'     => 'bank_transfer',
            'notes'              => 'Updated notes',
        ]);

        $response->assertRedirect(route('exchanges.show', $exchange));
        $response->assertSessionHas('success');

        $exchange->refresh();
        $this->assertEquals(50000, (float) $exchange->outgoing_value);
        $this->assertEquals(70000, (float) $exchange->incoming_value);
        $this->assertEquals('Updated Party', $exchange->counterparty);
        $this->assertEquals('paid', $exchange->cash_direction);
        $this->assertEquals(20000, (float) $exchange->cash_amount);

        // Goat Sales income should be updated to new outgoing value
        $this->assertDatabaseHas('transactions', [
            'type'     => 'income',
            'category' => 'Goat Sales',
            'amount'   => 50000,
        ]);

        // Old cash difference (25000) should be gone, new one (20000) created
        $this->assertDatabaseMissing('transactions', [
            'type'     => 'expense',
            'category' => 'Goat Purchases',
            'amount'   => 25000,
        ]);
        $this->assertDatabaseHas('transactions', [
            'type'     => 'expense',
            'category' => 'Goat Purchases',
            'amount'   => 20000,
        ]);
    }

    public function test_update_goat_values_are_synced(): void
    {
        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'EDIT-003',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'payment_method'     => 'cash',
        ]);

        $exchange = Exchange::where('incoming_tag_id', 'EDIT-003')->first();
        $incomingGoat = $exchange->incomingGoat;

        $this->put("/exchanges/{$exchange->id}", [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_value'     => 45000,
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'male',
            'incoming_value'     => 80000,
            'payment_method'     => 'cash',
        ]);

        // Incoming goat purchase_price should be updated
        $incomingGoat->refresh();
        $this->assertEquals(80000, (float) $incomingGoat->purchase_price);
        $this->assertEquals('male', $incomingGoat->gender);

        // Outgoing goat sale_price should be updated
        $this->goat->refresh();
        $this->assertEquals(45000, (float) $this->goat->sale_price);
    }

    public function test_update_can_assign_investor_after_creation(): void
    {
        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'EDIT-004',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'payment_method'     => 'cash',
        ]);

        $exchange = Exchange::where('incoming_tag_id', 'EDIT-004')->first();
        $investor = Investor::create([
            'name'            => 'Edit Investor',
            'role'            => 'investor',
            'expense_split_pct' => 33.33,
        ]);

        $this->put("/exchanges/{$exchange->id}", [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_value'     => 40000,
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'investor_id'        => $investor->id,
            'payment_method'     => 'cash',
        ]);

        $exchange->refresh();
        $this->assertEquals($investor->id, $exchange->investor_id);

        // Goat Sales income should now be tagged with the investor
        $this->assertDatabaseHas('transactions', [
            'type'        => 'income',
            'category'    => 'Goat Sales',
            'investor_id' => $investor->id,
        ]);
    }

    // ---- INVESTMENT CREATION TESTS ------------------------------------

    public function test_investor_cash_difference_creates_investment_record(): void
    {
        $investor = Investor::create([
            'name'              => 'Test Investor',
            'role'              => 'investor',
            'expense_split_pct' => 33.33,
        ]);

        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'INV-001',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'investor_id'        => $investor->id,
            'payment_method'     => 'cash',
        ]);

        // Investment record should exist for the 25,000 cash difference
        $this->assertDatabaseHas('investments', [
            'investor_id' => $investor->id,
            'amount'      => 25000,
        ]);

        // Investor's total_capital should now reflect the 25,000
        $investor->refresh();
        $this->assertEquals(25000, (float) $investor->total_capital);
    }

    public function test_no_investor_means_no_investment_created(): void
    {
        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'INV-002',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'payment_method'     => 'cash',
        ]);

        // No investment records should exist
        $this->assertEquals(0, \App\Models\Investment::count());
    }

    public function test_update_syncs_investment_when_investor_added(): void
    {
        // Create exchange without investor
        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'INV-003',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'payment_method'     => 'cash',
        ]);

        $exchange = Exchange::where('incoming_tag_id', 'INV-003')->first();
        $this->assertEquals(0, \App\Models\Investment::count());

        // Now assign an investor via edit
        $investor = Investor::create([
            'name'              => 'Assign Investor',
            'role'              => 'investor',
            'expense_split_pct' => 50,
        ]);

        $this->put("/exchanges/{$exchange->id}", [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_value'     => 40000,
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'investor_id'        => $investor->id,
            'payment_method'     => 'cash',
        ]);

        // Investment record should now exist
        $this->assertDatabaseHas('investments', [
            'investor_id' => $investor->id,
            'amount'      => 25000,
        ]);
    }

    public function test_update_syncs_investment_when_cash_amount_changes(): void
    {
        $investor = Investor::create([
            'name'              => 'Change Investor',
            'role'              => 'investor',
            'expense_split_pct' => 33.33,
        ]);

        // Create with 25,000 cash difference
        $this->post('/exchanges', [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_goat_id'   => $this->goat->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'INV-004',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 65000,
            'investor_id'        => $investor->id,
            'payment_method'     => 'cash',
        ]);

        $exchange = Exchange::where('incoming_tag_id', 'INV-004')->first();
        $this->assertEquals(25000, (float) \App\Models\Investment::first()->amount);

        // Edit: change values so cash difference becomes 35,000
        $this->put("/exchanges/{$exchange->id}", [
            'exchange_date'      => now()->format('Y-m-d'),
            'outgoing_value'     => 40000,
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_value'     => 75000,
            'investor_id'        => $investor->id,
            'payment_method'     => 'cash',
        ]);

        // Old 25,000 investment should be gone, new 35,000 should exist
        $this->assertDatabaseMissing('investments', [
            'investor_id' => $investor->id,
            'amount'      => 25000,
        ]);
        $this->assertDatabaseHas('investments', [
            'investor_id' => $investor->id,
            'amount'      => 35000,
        ]);
    }
}
