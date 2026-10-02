<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\Customer;
use App\Models\Goat;
use App\Models\Investor;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoatSaleHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'x']);
        $this->customer = Customer::create(['name' => 'Ali Raza']);
        $this->investor = Investor::create(['name' => 'Test Investor']);
        $this->actingAs($this->user);
    }

    public function test_sold_goat_page_shows_sale_card(): void
    {
        $goat = Goat::create([
            'tag_id' => 'TAG-H1', 'breed_id' => $this->breed->id,
            'gender' => 'male', 'status' => 'grower',
        ]);

        $this->post('/sales', [
            'goat_id'              => $goat->id,
            'customer_id'          => $this->customer->id,
            'seller_investor_id'   => $this->investor->id,
            'sale_date'            => '2026-10-01',
            'sale_price'           => 60000,
            'initial_paid_amount'  => 40000,
        ]);

        $response = $this->get("/goats/{$goat->id}");

        $response->assertStatus(200);
        $response->assertSee('Sale record');
        $response->assertSee('Ali Raza');
        $response->assertSee('Test Investor');
        $response->assertSee('Partial');   // partial payment status
        $response->assertSee('20,000');    // balance due
    }

    public function test_unsold_goat_page_shows_no_sale_card(): void
    {
        $goat = Goat::create([
            'tag_id' => 'TAG-H2', 'breed_id' => $this->breed->id,
            'gender' => 'female', 'status' => 'kid',
        ]);

        $response = $this->get("/goats/{$goat->id}");

        $response->assertStatus(200);
        $response->assertDontSee('Sale record');
    }

    public function test_legacy_backfilled_sale_renders_dashes(): void
    {
        $goat = Goat::create([
            'tag_id' => 'TAG-H3', 'breed_id' => $this->breed->id,
            'gender' => 'male', 'status' => 'grower',
        ]);
        Sale::create([
            'goat_id'   => $goat->id,
            'sale_date' => '2026-01-15',
            'sale_price'=> 20000,
            'status'    => 'paid',
            'notes'     => 'Backfilled',
        ]);
        $goat->update(['status' => 'sold', 'sale_price' => 20000, 'sale_date' => '2026-01-15']);

        $response = $this->get("/goats/{$goat->id}");

        $response->assertStatus(200);
        $response->assertSee('Sale record');
        $response->assertSee('TAG-H3');
        // buyer/seller render as em-dash placeholders
        $this->assertStringContainsString('—', $response->getContent());
        // no fatal errors and the card renders with the price
        $response->assertSee('20,000');
    }

    public function test_goats_index_links_sold_goat_to_its_sale(): void
    {
        $goat = Goat::create([
            'tag_id' => 'TAG-H4', 'breed_id' => $this->breed->id,
            'gender' => 'male', 'status' => 'grower',
        ]);
        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 25000,
        ]);
        $goat->refresh();

        $response = $this->get('/goats');

        $response->assertStatus(200);
        $response->assertSee('→ sale');
        $response->assertSee(route('sales.show', $goat->sale));
    }
}
