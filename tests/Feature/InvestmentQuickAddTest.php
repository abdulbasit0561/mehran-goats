<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentQuickAddTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_quick_add_investment(): void
    {
        $investor = Investor::factory()->create(['name' => 'Test Investor']);

        $response = $this->actingAs($this->user)->post(route('investments.quick-add'), [
            'investor_id' => $investor->id,
            'amount'      => 50000,
            'date'        => now()->format('Y-m-d'),
            'notes'       => 'Initial capital injection',
        ]);

        $response->assertRedirect(route('investors.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('investments', [
            'investor_id' => $investor->id,
            'amount'      => 50000,
        ]);
    }

    public function test_quick_add_requires_amount(): void
    {
        $investor = Investor::factory()->create();

        $response = $this->actingAs($this->user)->post(route('investments.quick-add'), [
            'investor_id' => $investor->id,
            'amount'      => '',
            'date'        => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_quick_add_requires_minimum_amount(): void
    {
        $investor = Investor::factory()->create();

        $response = $this->actingAs($this->user)->post(route('investments.quick-add'), [
            'investor_id' => $investor->id,
            'amount'      => 0,
            'date'        => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_quick_add_rejects_future_date(): void
    {
        $investor = Investor::factory()->create();

        $response = $this->actingAs($this->user)->post(route('investments.quick-add'), [
            'investor_id' => $investor->id,
            'amount'      => 1000,
            'date'        => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_quick_add_requires_valid_investor_id(): void
    {
        $response = $this->actingAs($this->user)->post(route('investments.quick-add'), [
            'investor_id' => 99999,
            'amount'      => 1000,
            'date'        => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('investor_id');
    }

    public function test_quick_add_requires_investor_id(): void
    {
        $response = $this->actingAs($this->user)->post(route('investments.quick-add'), [
            'investor_id' => '',
            'amount'      => 1000,
            'date'        => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('investor_id');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $investor = Investor::factory()->create();

        $response = $this->post(route('investments.quick-add'), [
            'investor_id' => $investor->id,
            'amount'      => 1000,
            'date'        => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_quick_add_records_multiple_investments_for_same_investor(): void
    {
        $investor = Investor::factory()->create();

        $this->actingAs($this->user)->post(route('investments.quick-add'), [
            'investor_id' => $investor->id,
            'amount'      => 20000,
            'date'        => now()->subDays(10)->format('Y-m-d'),
            'notes'       => 'First tranche',
        ]);

        $this->actingAs($this->user)->post(route('investments.quick-add'), [
            'investor_id' => $investor->id,
            'amount'      => 30000,
            'date'        => now()->format('Y-m-d'),
            'notes'       => 'Second tranche',
        ]);

        $this->assertEquals(2, $investor->fresh()->investments()->count());
        $this->assertEquals(50000, $investor->fresh()->total_capital);
    }

    public function test_investors_index_page_shows_quick_add_modal(): void
    {
        $response = $this->actingAs($this->user)->get(route('investors.index'));

        $response->assertOk();
        $response->assertSee('open-investment-modal');
        $response->assertSee(route('investments.quick-add'));
    }

    public function test_investors_index_shows_investor_dropdown_when_investors_exist(): void
    {
        $investor = Investor::factory()->create(['name' => 'John Doe']);

        $response = $this->actingAs($this->user)->get(route('investors.index'));

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertSee('quick-investor');
    }
}
