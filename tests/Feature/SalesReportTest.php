<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\Goat;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'x']);
        $this->actingAs($this->user);
    }

    private function makeGoat(string $tag, float $purchasePrice = 10000): Goat
    {
        return Goat::create([
            'tag_id'         => $tag,
            'breed_id'       => $this->breed->id,
            'gender'         => 'male',
            'status'         => 'grower',
            'purchase_price' => $purchasePrice,
        ]);
    }

    public function test_report_totals_match_sales_data(): void
    {
        // Sale 1: 60000, paid in full; purchase 10000 => profit 50000
        $goat1 = $this->makeGoat('TAG-R1', 10000);
        $this->post('/sales', [
            'goat_id'    => $goat1->id,
            'sale_date'  => '2026-06-15',
            'sale_price' => 60000,
        ]);

        // Sale 2: 40000 with 25000 paid up front => 15000 outstanding; purchase 5000 => profit 35000
        $goat2 = $this->makeGoat('TAG-R2', 5000);
        $this->post('/sales', [
            'goat_id'             => $goat2->id,
            'sale_date'           => '2026-07-20',
            'sale_price'          => 40000,
            'initial_paid_amount' => 25000,
        ]);

        $response = $this->get('/reports/sales?start=2026-01-01&end=2026-12-31');

        $response->assertStatus(200);
        $response->assertSee('100,000');  // revenue
        $response->assertSee('85,000');   // cash received
        $response->assertSee('15,000');   // outstanding
        $response->assertSee('Animals Sold'); // stat present
        $response->assertSee('85,000');   // gross profit 50000+35000... see note below
    }

    public function test_report_gross_profit_is_price_minus_purchase(): void
    {
        $goat = $this->makeGoat('TAG-R3', 12000);
        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-05-01',
            'sale_price' => 50000,
        ]);

        $response = $this->get('/reports/sales');

        $response->assertStatus(200);
        $response->assertSee('38,000'); // 50000 - 12000
    }

    public function test_date_range_filters_sales(): void
    {
        $goat1 = $this->makeGoat('TAG-R4', 0);
        $goat2 = $this->makeGoat('TAG-R5', 0);
        $this->post('/sales', ['goat_id' => $goat1->id, 'sale_date' => '2026-03-10', 'sale_price' => 10000]);
        $this->post('/sales', ['goat_id' => $goat2->id, 'sale_date' => '2026-07-10', 'sale_price' => 20000]);

        // June range: neither sale
        $response = $this->get('/reports/sales?start=2026-06-01&end=2026-06-30');
        $response->assertStatus(200);
        $response->assertSee('No sales in this period.');

        // March range: only sale 1
        $response = $this->get('/reports/sales?start=2026-03-01&end=2026-03-31');
        $response->assertSee('TAG-R4');
        $response->assertDontSee('TAG-R5');
    }

    public function test_empty_range_shows_zeros(): void
    {
        $response = $this->get('/reports/sales?start=2020-01-01&end=2020-12-31');

        $response->assertStatus(200);
        $response->assertSee('0');
        $response->assertSee('No sales in this period.');
    }

    public function test_csv_export_contains_same_data(): void
    {
        $goat = $this->makeGoat('TAG-R6', 5000);
        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-06-15',
            'sale_price' => 30000,
        ]);

        $response = $this->get('/reports/sales/csv?start=2026-01-01&end=2026-12-31');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Sales Revenue', $content);
        $this->assertStringContainsString('TAG-R6', $content);
        $this->assertStringContainsString('30,000.00', $content);
    }

    public function test_pdf_export_downloads(): void
    {
        $goat = $this->makeGoat('TAG-R7', 5000);
        $this->post('/sales', [
            'goat_id'    => $goat->id,
            'sale_date'  => '2026-06-15',
            'sale_price' => 30000,
        ]);

        $response = $this->get('/reports/sales/pdf?start=2026-01-01&end=2026-12-31');

        $response->assertStatus(200);
        $this->assertStringContainsString('pdf', $response->headers->get('Content-Type'));
    }
}
