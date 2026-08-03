<?php

namespace Tests\Feature;

use App\Models\Investor;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionBulkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_bulk_delete_removes_selected_transactions(): void
    {
        $t1 = Transaction::create(['type' => 'expense', 'category' => 'Feed', 'amount' => 100, 'date' => '2026-06-01']);
        $t2 = Transaction::create(['type' => 'expense', 'category' => 'Vet',  'amount' => 200, 'date' => '2026-06-02']);
        $t3 = Transaction::create(['type' => 'expense', 'category' => 'Labor', 'amount' => 300, 'date' => '2026-06-03']); // not selected

        $response = $this->delete('/transactions/bulk', [
            'ids' => [$t1->id, $t2->id],
        ]);

        $response->assertRedirect('/transactions');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('transactions', ['id' => $t1->id]);
        $this->assertDatabaseMissing('transactions', ['id' => $t2->id]);
        $this->assertDatabaseHas('transactions', ['id' => $t3->id]);
    }

    public function test_bulk_delete_validation_rejects_empty_ids(): void
    {
        $response = $this->delete('/transactions/bulk', [
            'ids' => [],
        ]);

        $response->assertSessionHasErrors('ids');
    }

    public function test_bulk_delete_requires_authentication(): void
    {
        auth()->logout();

        $t1 = Transaction::create(['type' => 'expense', 'category' => 'Feed', 'amount' => 100, 'date' => '2026-06-01']);

        $response = $this->delete('/transactions/bulk', [
            'ids' => [$t1->id],
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('transactions', ['id' => $t1->id]);
    }

    public function test_bulk_assign_investor_updates_selected_transactions(): void
    {
        $investor = Investor::create(['name' => 'Alice']);

        $t1 = Transaction::create(['type' => 'expense', 'category' => 'Feed', 'amount' => 100, 'date' => '2026-06-01']);
        $t2 = Transaction::create(['type' => 'expense', 'category' => 'Vet',  'amount' => 200, 'date' => '2026-06-02']);
        $t3 = Transaction::create(['type' => 'expense', 'category' => 'Labor', 'amount' => 300, 'date' => '2026-06-03']); // not selected

        $response = $this->patch('/transactions/bulk', [
            'ids'         => [$t1->id, $t2->id],
            'investor_id' => $investor->id,
        ]);

        $response->assertRedirect('/transactions');
        $response->assertSessionHas('success');

        $this->assertEquals($investor->id, $t1->fresh()->investor_id);
        $this->assertEquals($investor->id, $t2->fresh()->investor_id);
        $this->assertNull($t3->fresh()->investor_id);
    }

    public function test_bulk_assign_rejects_invalid_investor_id(): void
    {
        $t1 = Transaction::create(['type' => 'expense', 'category' => 'Feed', 'amount' => 100, 'date' => '2026-06-01']);

        $response = $this->patch('/transactions/bulk', [
            'ids'         => [$t1->id],
            'investor_id' => 99999,
        ]);

        $response->assertSessionHasErrors('investor_id');
    }

    public function test_bulk_assign_requires_authentication(): void
    {
        auth()->logout();

        $investor = Investor::create(['name' => 'Alice']);
        $t1 = Transaction::create(['type' => 'expense', 'category' => 'Feed', 'amount' => 100, 'date' => '2026-06-01']);

        $response = $this->patch('/transactions/bulk', [
            'ids'         => [$t1->id],
            'investor_id' => $investor->id,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_transactions_index_shows_bulk_action_elements(): void
    {
        $response = $this->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('id="bulkActionBar"', false);
        $response->assertSee('id="txnSelectAll"', false);
        $response->assertSee('txn-row-check', false);
        $response->assertSee('txn-mobile-check', false);
    }
}
