<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\Goat;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoatCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
        $this->actingAs($this->user);
    }

    public function test_can_list_goats(): void
    {
        Goat::create([
            'tag_id'   => 'TAG-001',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'kid',
        ]);

        $response = $this->get('/goats');

        $response->assertStatus(200);
        $response->assertSee('TAG-001');
    }

    public function test_can_create_a_goat(): void
    {
        $response = $this->post('/goats', [
            'tag_id'   => 'TAG-100',
            'breed_id' => $this->breed->id,
            'gender'   => 'female',
            'status'   => 'kid',
        ]);

        $response->assertRedirect('/goats');
        $this->assertDatabaseHas('goats', ['tag_id' => 'TAG-100']);
    }

    public function test_tag_id_must_be_unique(): void
    {
        Goat::create([
            'tag_id'   => 'TAG-DUP',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'kid',
        ]);

        $response = $this->post('/goats', [
            'tag_id'   => 'TAG-DUP',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'kid',
        ]);

        $response->assertSessionHasErrors('tag_id');
    }

    public function test_can_update_a_goat(): void
    {
        $goat = Goat::create([
            'tag_id'   => 'TAG-UP',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'kid',
        ]);

        $this->put("/goats/{$goat->id}", [
            'tag_id'   => 'TAG-UP',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'breeder',
        ]);

        $this->assertDatabaseHas('goats', ['id' => $goat->id, 'status' => 'breeder']);
    }

    public function test_marking_goat_sold_creates_income_transaction(): void
    {
        $goat = Goat::create([
            'tag_id'         => 'TAG-SOLD',
            'breed_id'       => $this->breed->id,
            'gender'         => 'male',
            'status'         => 'kid',
            'purchase_price' => 10000,
        ]);

        $this->put("/goats/{$goat->id}", [
            'tag_id'         => 'TAG-SOLD',
            'breed_id'       => $this->breed->id,
            'gender'         => 'male',
            'status'         => 'sold',
            'sale_price'     => 18000,
            'sale_date'      => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('transactions', [
            'type'     => 'income',
            'category' => 'Goat Sales',
            'amount'   => 18000,
            'goat_id'  => $goat->id,
        ]);
    }

    public function test_no_duplicate_sale_transaction_on_resave(): void
    {
        $goat = Goat::create([
            'tag_id'         => 'TAG-DUP',
            'breed_id'       => $this->breed->id,
            'gender'         => 'male',
            'status'         => 'sold',
            'sale_price'     => 5000,
            'sale_date'      => now(),
            'purchase_price' => 3000,
        ]);

        Transaction::create([
            'type' => 'income', 'category' => 'Goat Sales', 'amount' => 5000,
            'date' => now(), 'goat_id' => $goat->id, 'payment_method' => 'cash',
        ]);

        $this->put("/goats/{$goat->id}", [
            'tag_id'     => 'TAG-DUP',
            'breed_id'   => $this->breed->id,
            'gender'     => 'male',
            'status'     => 'sold',
            'sale_price' => 5000,
        ]);

        $this->assertSame(
            1,
            Transaction::where('goat_id', $goat->id)->where('category', 'Goat Sales')->count()
        );
    }

    public function test_can_delete_a_goat(): void
    {
        $goat = Goat::create([
            'tag_id'   => 'TAG-DEL',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'kid',
        ]);

        $this->delete("/goats/{$goat->id}");

        $this->assertSoftDeleted('goats', ['id' => $goat->id]);
    }
}
