<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\Customer;
use App\Models\Goat;
use App\Models\Investor;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaleModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
        $this->investor = Investor::create(['name' => 'Test Investor']);
        $this->customer = Customer::create(['name' => 'Ali Raza']);
        $this->actingAs($this->user);
    }

    private function makeGoat(string $tag = 'TAG-S1', string $status = 'grower'): Goat
    {
        return Goat::create([
            'tag_id'   => $tag,
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => $status,
        ]);
    }

    private function goatSalesIncome(Goat $goat)
    {
        return Transaction::where('type', 'income')
            ->where('category', 'Goat Sales')
            ->where('goat_id', $goat->id);
    }

    public function test_recording_sale_marks_goat_sold_and_creates_one_income(): void
    {
        $goat = $this->makeGoat();

        $response = $this->post('/sales', [
            'goat_id'            => $goat->id,
            'customer_id'        => $this->customer->id,
            'seller_investor_id' => $this->investor->id,
            'sale_date'          => '2026-10-01',
            'sale_price'         => 50000,
            'sale_weight'        => 45.5,
            'payment_method'     => 'cash',
            'notes'              => 'Test sale',
        ]);

        $sale = Sale::where('goat_id', $goat->id)->first();
        $response->assertRedirect(route('sales.show', $sale));

        $goat->refresh();
        $this->assertEquals('sold', $goat->status);
        $this->assertEquals(50000, (float) $goat->sale_price);

        $this->assertEquals(1, $this->goatSalesIncome($goat)->count());
        $tx = $this->goatSalesIncome($goat)->first();
        $this->assertEquals($sale->id, $tx->sale_id);
        $this->assertEquals(50000, (float) $tx->amount);
    }

    public function test_cannot_sell_already_sold_goat(): void
    {
        $goat = $this->makeGoat();

        $this->post('/sales', ['goat_id' => $goat->id, 'sale_date' => '2026-10-01', 'sale_price' => 10000]);

        $response = $this->post('/sales', ['goat_id' => $goat->id, 'sale_date' => '2026-10-02', 'sale_price' => 12000]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, Sale::where('goat_id', $goat->id)->count());
        $this->assertEquals(1, $this->goatSalesIncome($goat)->count());
    }

    public function test_sale_without_photo_and_without_buyer_succeeds(): void
    {
        $goat = $this->makeGoat();

        $response = $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 25000,
        ]);

        $response->assertSessionHas('success');
        $sale = Sale::where('goat_id', $goat->id)->first();
        $this->assertNull($sale->photo_path);
        $this->assertNull($sale->customer_id);
    }

    public function test_sale_with_photo_uploads_and_links_file(): void
    {
        Storage::fake('public');
        $goat = $this->makeGoat();

        $response = $this->post('/sales', [
            'goat_id'    => $goat->id,
            'customer_id'=> $this->customer->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 30000,
            'photo'      => UploadedFile::fake()->image('sold-goat.jpg'),
        ]);

        $response->assertSessionHas('success');
        $sale = Sale::where('goat_id', $goat->id)->first();
        $this->assertNotNull($sale->photo_path);
        Storage::disk('public')->assertExists($sale->photo_path);
    }

    public function test_goat_edit_mark_sold_creates_linked_sale_without_duplicate_income(): void
    {
        $goat = $this->makeGoat();

        // Mark sold via the goat edit path
        $this->put("/goats/{$goat->id}", [
            'tag_id'     => $goat->tag_id,
            'breed_id'   => $this->breed->id,
            'gender'     => 'male',
            'status'     => 'sold',
            'sale_price' => 15000,
            'sale_date'  => '2026-09-30',
        ]);

        $this->assertEquals(1, Sale::where('goat_id', $goat->id)->count());
        $this->assertEquals(1, $this->goatSalesIncome($goat)->count());

        // Re-save the goat without changes — no double income
        $this->put("/goats/{$goat->id}", [
            'tag_id'     => $goat->tag_id,
            'breed_id'   => $this->breed->id,
            'gender'     => 'male',
            'status'     => 'sold',
            'sale_price' => 15000,
            'sale_date'  => '2026-09-30',
        ]);

        $this->assertEquals(1, Sale::where('goat_id', $goat->id)->count(), 'sale duplicated');
        $this->assertEquals(1, $this->goatSalesIncome($goat)->count(), 'income duplicated');
    }

    public function test_sales_list_shows_legacy_sales_with_dashes(): void
    {
        $goat = $this->makeGoat();
        Sale::create([
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-01-15',
            'sale_price' => 20000,
            'status'     => 'paid',
            'notes'      => 'Backfilled',
        ]);

        $response = $this->get('/sales');

        $response->assertStatus(200);
        $response->assertSee('TAG-S1');
    }

    public function test_updating_sale_updates_income_exactly_once(): void
    {
        $goat = $this->makeGoat();
        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 40000,
        ]);
        $sale = Sale::where('goat_id', $goat->id)->first();

        // Edit form does NOT submit goat_id — must still succeed.
        $this->put("/sales/{$sale->id}", [
            'customer_id'=> $this->customer->id,
            'sale_date'  => '2026-10-02',
            'sale_price' => 45000,
        ]);

        $sale->refresh();
        $this->assertEquals(45000, (float) $sale->sale_price);
        $this->assertEquals(1, $this->goatSalesIncome($goat)->count());
        $this->assertEquals(45000, (float) $this->goatSalesIncome($goat)->first()->amount);
    }

    public function test_deleting_buyer_with_sale_is_blocked(): void
    {
        $goat = $this->makeGoat();
        $this->post('/sales', [
            'goat_id'     => $goat->id,
            'customer_id' => $this->customer->id,
            'sale_date'   => '2026-10-01',
            'sale_price'  => 40000,
        ]);

        $response = $this->delete("/customers/{$this->customer->id}");

        $response->assertRedirect(route('customers.show', $this->customer));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('customers', ['id' => $this->customer->id, 'deleted_at' => null]);
    }

    public function test_deleting_sale_returns_goat_to_herd_and_removes_income(): void
    {
        $goat = $this->makeGoat();
        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-10-01',
            'sale_price' => 40000,
        ]);
        $sale = Sale::where('goat_id', $goat->id)->first();

        $response = $this->delete("/sales/{$sale->id}");

        $response->assertRedirect(route('sales.index'));
        $this->assertEquals(0, $this->goatSalesIncome($goat)->count());
        $goat->refresh();
        $this->assertEquals('grower', $goat->status);
        $this->assertEquals(0, Sale::where('goat_id', $goat->id)->count());
    }

    public function test_backfill_is_idempotent_and_creates_no_new_income(): void
    {
        $goat = $this->makeGoat(status: 'sold');
        $goat->update(['sale_price' => 60000, 'sale_date' => '2026-08-01']);
        Transaction::create([
            'type'     => 'income',
            'category' => 'Goat Sales',
            'amount'   => 60000,
            'date'     => '2026-08-01',
            'goat_id'  => $goat->id,
        ]);

        $this->artisan('sales:backfill');
        $this->artisan('sales:backfill'); // second run must be a no-op

        $this->assertEquals(1, Sale::where('goat_id', $goat->id)->count());
        $this->assertEquals(1, $this->goatSalesIncome($goat)->count());
        $this->assertEquals(60000, (float) $this->goatSalesIncome($goat)->sum('amount'));
    }

    public function test_price_must_be_positive(): void
    {
        $goat = $this->makeGoat();

        $response = $this->post('/sales', [
            'goat_id'   => $goat->id,
            'sale_date' => '2026-10-01',
            'sale_price'=> 0,
        ]);

        $response->assertSessionHasErrors('sale_price');
        $this->assertEquals(0, Sale::count());
    }
}
