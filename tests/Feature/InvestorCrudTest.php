<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestorCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_investors(): void
    {
        Investor::create(['name' => 'Alice']);

        $response = $this->get('/investors');

        $response->assertStatus(200);
        $response->assertSee('Alice');
    }

    public function test_can_create_an_investor(): void
    {
        $response = $this->post('/investors', [
            'name'  => 'Bob Builder',
            'role'  => 'investor',
            'email' => 'bob@test.com',
            'phone' => '555-1234',
        ]);

        $response->assertRedirect('/investors');
        $this->assertDatabaseHas('investors', ['name' => 'Bob Builder']);
    }

    public function test_can_add_investment_to_investor(): void
    {
        $investor = Investor::create(['name' => 'Charlie']);

        $response = $this->post("/investors/{$investor->id}/investments", [
            'amount' => 5000,
            'date'   => '2026-06-01',
            'notes'  => 'Initial capital',
        ]);

        $response->assertRedirect("/investors/{$investor->id}");
        $this->assertDatabaseHas('investments', ['investor_id' => $investor->id, 'amount' => 5000]);
    }

    public function test_share_pct_recalculates_on_new_investment(): void
    {
        $a = Investor::create(['name' => 'Alice']);
        $b = Investor::create(['name' => 'Bob']);

        Investment::create(['investor_id' => $a->id, 'amount' => 1000, 'date' => '2026-01-01']);

        // Alice is sole investor: 100%
        $this->assertEquals(100.0, $a->fresh()->share_pct);

        // Bob invests 1000: now 50/50
        Investment::create(['investor_id' => $b->id, 'amount' => 1000, 'date' => '2026-01-02']);

        $this->assertEquals(50.0, $a->fresh()->share_pct);
        $this->assertEquals(50.0, $b->fresh()->share_pct);
    }
}
