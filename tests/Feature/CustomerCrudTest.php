<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_buyer(): void
    {
        $response = $this->post('/customers', [
            'name'    => 'Ali Raza',
            'phone'   => '0300-1234567',
            'address' => 'Lahore',
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $customer = Customer::where('name', 'Ali Raza')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('0300-1234567', $customer->phone);
        $this->assertEquals('Lahore', $customer->address);
    }

    public function test_name_is_required(): void
    {
        $response = $this->post('/customers', ['name' => '']);

        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Customer::count());
    }

    public function test_can_update_buyer(): void
    {
        $customer = Customer::create(['name' => 'Ali Raza']);

        $response = $this->put("/customers/{$customer->id}", [
            'name'  => 'Ali Raza Khan',
            'phone' => '0321-9998887',
        ]);

        $response->assertRedirect(route('customers.show', $customer));
        $customer->refresh();
        $this->assertEquals('Ali Raza Khan', $customer->name);
        $this->assertEquals('0321-9998887', $customer->phone);
    }

    public function test_can_delete_buyer_without_sales(): void
    {
        $customer = Customer::create(['name' => 'Ali Raza']);

        $response = $this->delete("/customers/{$customer->id}");

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('customers', ['id' => $customer->id, 'deleted_at' => null]);
    }

    public function test_search_finds_buyer_by_name(): void
    {
        Customer::create(['name' => 'Ali Raza']);
        Customer::create(['name' => 'Bilal Khan']);

        $response = $this->get('/customers?q=Ali');

        $response->assertStatus(200);
        $response->assertSee('Ali Raza');
        $response->assertDontSee('Bilal Khan');
    }

    public function test_buyers_index_renders(): void
    {
        Customer::create(['name' => 'Ali Raza', 'phone' => '0300-1234567']);

        $response = $this->get('/customers');

        $response->assertStatus(200);
        $response->assertSee('Buyers');
        $response->assertSee('Ali Raza');
    }
}
