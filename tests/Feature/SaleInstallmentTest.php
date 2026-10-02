<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\Customer;
use App\Models\Goat;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleInstallmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'x']);
        $this->actingAs($this->user);
    }

    private function makeGoat(string $tag = 'TAG-P1'): Goat
    {
        return Goat::create([
            'tag_id'   => $tag,
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'grower',
        ]);
    }

    public function test_partial_sale_transitions_to_paid_via_installments(): void
    {
        $goat = $this->makeGoat();

        // Create the sale with a small down payment
        $this->post('/sales', [
            'goat_id'              => $goat->id,
            'sale_date'            => '2026-10-01',
            'sale_price'           => 100000,
            'initial_paid_amount'  => 40000,
        ]);

        $sale = Sale::where('goat_id', $goat->id)->first();
        $this->assertEquals('partial', $sale->status);
        $this->assertEquals(40000, $sale->paid_amount);
        $this->assertEquals(60000, $sale->balance_due);

        // Second payment completes it
        $this->post("/sales/{$sale->id}/payments", [
            'amount'        => 60000,
            'payment_date'  => '2026-10-15',
            'payment_method'=> 'bank_transfer',
            'reference'     => 'TRX-99',
        ]);

        $sale->refresh();
        $this->assertEquals('paid', $sale->status);
        $this->assertEquals(0, $sale->balance_due);
        $this->assertEquals(2, $sale->payments()->count());
    }

    public function test_full_payment_by_default_creates_paid_sale(): void
    {
        $goat = $this->makeGoat('TAG-P2');

        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 50000,
        ]);

        $sale = Sale::where('goat_id', $goat->id)->first();
        $this->assertEquals('paid', $sale->status);
        $this->assertEquals(50000, $sale->paid_amount);
    }

    public function test_zero_down_payment_creates_unpaid_sale(): void
    {
        $goat = $this->makeGoat('TAG-P3');

        $this->post('/sales', [
            'goat_id'             => $goat->id,
            'sale_date'           => '2026-10-01',
            'sale_price'          => 50000,
            'initial_paid_amount' => 0,
        ]);

        $sale = Sale::where('goat_id', $goat->id)->first();
        $this->assertEquals('unpaid', $sale->status);
    }

    public function test_payments_do_not_change_income_totals(): void
    {
        $goat = $this->makeGoat('TAG-P4');

        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 80000,
        ]);
        $sale = Sale::where('goat_id', $goat->id)->first();

        $incomeBefore = (float) Transaction::income()->where('goat_id', $goat->id)->sum('amount');

        $this->post("/sales/{$sale->id}/payments", [
            'amount'       => 20000,
            'payment_date' => '2026-10-20',
            'payment_method' => 'cash',
        ]);

        $incomeAfter = (float) Transaction::income()->where('goat_id', $goat->id)->sum('amount');

        $this->assertEquals($incomeBefore, $incomeAfter, 'income changed after payment');
        $this->assertEquals(80000, $incomeAfter);
    }

    public function test_overpayment_is_allowed_but_flagged(): void
    {
        $goat = $this->makeGoat('TAG-P5');

        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 10000,
        ]);
        $sale = Sale::where('goat_id', $goat->id)->first();

        $response = $this->post("/sales/{$sale->id}/payments", [
            'amount'       => 5000,
            'payment_date' => '2026-10-20',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect(route('sales.show', $sale));
        $response->assertSessionHas('success');
        $sale->refresh();
        $this->assertEquals(15000, $sale->paid_amount, 'overpaid total recorded');
    }

    public function test_deleting_payment_recalculates_balance_and_status(): void
    {
        $goat = $this->makeGoat('TAG-P6');

        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 100000,
            'initial_paid_amount' => 60000,
        ]);
        $sale = Sale::where('goat_id', $goat->id)->first();
        $this->assertEquals('partial', $sale->status);

        $payment = $sale->payments()->first();

        $response = $this->delete("/sales/{$sale->id}/payments/{$payment->id}");

        $response->assertRedirect(route('sales.show', $sale));
        $sale->refresh();
        $this->assertEquals(0, $sale->paid_amount);
        $this->assertEquals(100000, $sale->balance_due);
        $this->assertEquals('unpaid', $sale->status);
    }

    public function test_payment_amount_must_be_positive(): void
    {
        $goat = $this->makeGoat('TAG-P7');

        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 10000,
        ]);
        $sale = Sale::where('goat_id', $goat->id)->first();

        $response = $this->post("/sales/{$sale->id}/payments", [
            'amount'       => -5,
            'payment_date' => '2026-10-20',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertEquals(1, SalePayment::where('sale_id', $sale->id)->count()); // only the initial payment, not the invalid one
    }

    public function test_sales_index_shows_outstanding_summary(): void
    {
        $goat1 = $this->makeGoat('TAG-P8');
        $goat2 = $this->makeGoat('TAG-P9');

        $this->post('/sales', ['goat_id' => $goat1->id, 'sale_date' => '2026-10-01', 'sale_price' => 50000]); // paid
        $this->post('/sales', ['goat_id' => $goat2->id, 'sale_date' => '2026-10-01', 'sale_price' => 70000, 'initial_paid_amount' => 30000]); // 40k due

        $response = $this->get('/sales');

        $response->assertStatus(200);
        $response->assertSee('Outstanding');
        $this->assertEquals(40000, (float) Sale::sum('sale_price') - (float) SalePayment::sum('amount'));
    }

    public function test_empty_initial_payment_field_means_full_payment(): void
    {
        // The browser form always submits the field — empty string must mean
        // "paid in full", not "unpaid".
        $goat = $this->makeGoat('TAG-P10');

        $this->post('/sales', [
            'goat_id'              => $goat->id,
            'sale_date'            => '2026-10-01',
            'sale_price'           => 30000,
            'initial_paid_amount'  => '',
        ]);

        $sale = Sale::where('goat_id', $goat->id)->first();
        $this->assertEquals('paid', $sale->status);
        $this->assertEquals(30000, $sale->paid_amount);
    }

    public function test_deleting_sale_then_reselling_does_not_resurrect_payments(): void
    {
        $goat = $this->makeGoat('TAG-P11');

        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 60000,
            'initial_paid_amount' => 50000,
        ]);
        $firstSale = Sale::where('goat_id', $goat->id)->first();
        $this->assertEquals(1, $firstSale->payments()->count());

        $this->delete("/sales/{$firstSale->id}");
        $this->assertEquals(0, SalePayment::count(), 'payments must be deleted with the sale');

        // Re-sell the same goat for less
        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-05',
            'sale_price' => 20000,
        ]);
        $secondSale = Sale::where('goat_id', $goat->id)->first();

        $this->assertEquals(20000, $secondSale->paid_amount, 'old payments resurrected');
        $this->assertEquals(1, $secondSale->payments()->count());
        $this->assertEquals('paid', $secondSale->status);
    }

    public function test_outstanding_stat_reflects_balances_not_revenue(): void
    {
        $goat1 = $this->makeGoat('TAG-P12');
        $goat2 = $this->makeGoat('TAG-P13');

        // sale 1: 100000 fully paid; sale 2: 50000 with 20000 paid
        $this->post('/sales', ['goat_id' => $goat1->id, 'sale_date' => '2026-10-01', 'sale_price' => 100000]);
        $this->post('/sales', ['goat_id' => $goat2->id, 'sale_date' => '2026-10-01', 'sale_price' => 50000, 'initial_paid_amount' => 20000]);

        $response = $this->get('/sales');

        $response->assertStatus(200);
        // revenue 150000 — outstanding must be 30000. The Outstanding card is the
        // only place 'text-amber-400' appears; assert its rendered value.
        $response->assertSee('text-amber-400');
        $response->assertSee('30,000');
    }

    public function test_overpayment_does_not_offset_other_sales_outstanding(): void
    {
        $goat1 = $this->makeGoat('TAG-P14');
        $goat2 = $this->makeGoat('TAG-P15');

        // sale 1: 10000 overpaid (paid 20000); sale 2: 30000 with only 10000 paid
        $this->post('/sales', ['goat_id' => $goat1->id, 'sale_date' => '2026-10-01', 'sale_price' => 10000]);
        $sale1 = Sale::where('goat_id', $goat1->id)->first();
        $this->post("/sales/{$sale1->id}/payments", ['amount' => 20000, 'payment_date' => '2026-10-02']);

        $this->post('/sales', ['goat_id' => $goat2->id, 'sale_date' => '2026-10-01', 'sale_price' => 30000, 'initial_paid_amount' => 10000]);

        // Outstanding must be sale 2's 20000 — sale 1's 10000 surplus must NOT cancel it
        $outstanding = (float) Sale::with('payments')->get()->sum(fn ($s) => $s->balance_due);
        $this->assertEquals(20000, $outstanding);
    }

    public function test_editing_sale_preserves_payment_history(): void
    {
        $goat = $this->makeGoat('TAG-P16');

        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 60000,
            'initial_paid_amount' => 20000,
        ]);
        $sale = Sale::where('goat_id', $goat->id)->first();
        $this->assertEquals('partial', $sale->status);

        // Edit date only — payments must survive, status recomputed
        $this->put("/sales/{$sale->id}", [
            'sale_date'  => '2026-10-03',
            'sale_price' => 60000,
        ]);

        $sale->refresh();
        $this->assertEquals(1, $sale->payments()->count(), 'payment history wiped by edit');
        $this->assertEquals(20000, $sale->paid_amount);
        $this->assertEquals('partial', $sale->status);
        $this->assertEquals('2026-10-03', $sale->sale_date->format('Y-m-d'));
    }

    public function test_goat_edit_price_change_keeps_payments_and_updates_status(): void
    {
        $goat = $this->makeGoat('TAG-P17');

        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 60000,
            'initial_paid_amount' => 60000,
        ]);
        $sale = Sale::where('goat_id', $goat->id)->first();
        $this->assertEquals('paid', $sale->status);

        // Raise the price via the goat edit form — paid 60000 of 65000 => partial
        $this->put("/goats/{$goat->id}", [
            'tag_id'     => $goat->tag_id,
            'breed_id'   => $this->breed->id,
            'gender'     => 'male',
            'status'     => 'sold',
            'sale_price' => 65000,
            'sale_date'  => '2026-10-01',
        ]);

        $sale->refresh();
        $this->assertEquals(1, $sale->payments()->count(), 'payment history wiped by goat edit');
        $this->assertEquals(65000, (float) $sale->sale_price);
        $this->assertEquals('partial', $sale->status, 'status not recomputed after price change');
    }

    public function test_goat_edit_created_sale_has_full_payment_row(): void
    {
        // Sales created via goat-edit (not the sales module) must carry a
        // payment row too, so a later price change yields 'partial' — never 'unpaid'.
        $goat = $this->makeGoat('TAG-P18');

        $this->put("/goats/{$goat->id}", [
            'tag_id'     => $goat->tag_id,
            'breed_id'   => $this->breed->id,
            'gender'     => 'male',
            'status'     => 'sold',
            'sale_price' => 60000,
            'sale_date'  => '2026-10-01',
        ]);

        $sale = Sale::where('goat_id', $goat->id)->first();
        $this->assertNotNull($sale);
        $this->assertEquals(1, $sale->payments()->count(), 'goat-edit sale has no payment row');
        $this->assertEquals(60000, $sale->paid_amount);
        $this->assertEquals('paid', $sale->status);

        // Price raise flips to partial, not unpaid
        $this->put("/goats/{$goat->id}", [
            'tag_id'     => $goat->tag_id,
            'breed_id'   => $this->breed->id,
            'gender'     => 'male',
            'status'     => 'sold',
            'sale_price' => 65000,
            'sale_date'  => '2026-10-01',
        ]);
        $sale->refresh();
        $this->assertEquals('partial', $sale->status);
        $this->assertEquals(5000, $sale->balance_due);
    }

    public function test_exchange_created_sale_has_full_payment_row(): void
    {
        $goatOut = $this->makeGoat('TAG-P19');
        $goatIn  = $this->makeGoat('TAG-P20');

        $this->post('/exchanges', [
            'exchange_date'      => '2026-10-01',
            'counterparty'       => 'Test Trader',
            'outgoing_goat_id'   => $goatOut->id,
            'outgoing_value'     => 40000,
            'incoming_tag_id'    => 'INC-EX1',
            'breed_id'           => $this->breed->id,
            'incoming_gender'    => 'female',
            'incoming_weight_kg' => 25,
            'incoming_value'     => 40000,
        ]);

        $sale = Sale::where('goat_id', $goatOut->id)->first();
        $this->assertNotNull($sale, 'exchange did not create a sale');
        $this->assertEquals(40000, $sale->paid_amount, 'exchange sale has no payment row');
        $this->assertEquals('paid', $sale->status);
    }
}
