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
}
