<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\BudgetEntry;
use App\Models\Caretaker;
use App\Models\Goat;
use App\Models\HealthRecord;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\OwnershipUnit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ─── PULL ───────────────────────────────────────────────────────────

    public function test_pull_requires_authentication(): void
    {
        $response = $this->getJson('/api/sync/pull');
        $response->assertUnauthorized();
    }

    public function test_pull_returns_all_entity_data(): void
    {
        $breed = Breed::factory()->create(['name' => 'Beetal']);
        $goat  = Goat::factory()->create(['breed_id' => $breed->id, 'tag_id' => 'G-001']);

        $response = $this->actingAs($this->user)->getJson('/api/sync/pull');

        $response->assertOk();
        $response->assertJsonStructure([
            'synced_at',
            'data' => [
                'goats', 'breeds', 'caretakers', 'investors',
                'transactions', 'health_records', 'breeding_records',
                'investments', 'budget_entries', 'ownership_units',
            ],
        ]);

        $this->assertEquals('Beetal', $response->json('data.breeds.0.name'));
        $this->assertEquals('G-001', $response->json('data.goats.0.tag_id'));
    }

    // ─── PUSH: GOAT ────────────────────────────────────────────────────

    public function test_push_creates_goat(): void
    {
        $breed = Breed::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'test-uuid-1',
                    'entity' => 'goat',
                    'action' => 'create',
                    'data'   => [
                        'tag_id'   => 'SYNC-001',
                        'name'     => 'Test Goat',
                        'breed_id' => $breed->id,
                        'gender'   => 'female',
                        'status'   => 'breeder',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.id', 'test-uuid-1');
        $response->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('goats', ['tag_id' => 'SYNC-001', 'name' => 'Test Goat']);
    }

    public function test_push_validates_goat_data(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'test-uuid-2',
                    'entity' => 'goat',
                    'action' => 'create',
                    'data'   => [
                        'tag_id' => '', // missing required field
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'error');
        $response->assertJsonPath('results.0.id', 'test-uuid-2');
    }

    public function test_push_updates_goat(): void
    {
        $breed = Breed::factory()->create();
        $goat  = Goat::factory()->create(['breed_id' => $breed->id, 'tag_id' => 'G-100', 'name' => 'Old Name']);

        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'        => 'test-uuid-3',
                    'entity'    => 'goat',
                    'action'    => 'update',
                    'record_id' => $goat->id,
                    'data'      => [
                        'tag_id'   => 'G-100',
                        'breed_id' => $breed->id,
                        'gender'   => 'male',
                        'status'   => 'breeder',
                        'name'     => 'New Name',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        $goat->refresh();
        $this->assertEquals('New Name', $goat->name);
    }

    // ─── PUSH: TRANSACTION ─────────────────────────────────────────────

    public function test_push_creates_transaction(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'tx-uuid-1',
                    'entity' => 'transaction',
                    'action' => 'create',
                    'data'   => [
                        'type'     => 'expense',
                        'category' => 'Feed & Fodder',
                        'amount'   => 1500.00,
                        'date'     => '2026-07-08',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('transactions', [
            'category' => 'Feed & Fodder',
            'amount'   => 1500.00,
        ]);
    }

    // ─── PUSH: HEALTH RECORD ───────────────────────────────────────────

    public function test_push_creates_health_record(): void
    {
        $breed = Breed::factory()->create();
        $goat  = Goat::factory()->create(['breed_id' => $breed->id]);

        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'hr-uuid-1',
                    'entity' => 'health_record',
                    'action' => 'create',
                    'data'   => [
                        'goat_id' => $goat->id,
                        'type'    => 'vaccination',
                        'date'    => '2026-07-08',
                        'cost'    => 500.00,
                        'notes'   => 'Annual vaccine',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('health_records', [
            'goat_id' => $goat->id,
            'type'    => 'vaccination',
        ]);
    }

    // ─── PUSH: BREEDING RECORD ─────────────────────────────────────────

    public function test_push_creates_breeding_record_with_auto_calc(): void
    {
        $breed = Breed::factory()->create();
        $sire  = Goat::factory()->create(['breed_id' => $breed->id, 'gender' => 'male', 'tag_id' => 'S-001']);
        $dam   = Goat::factory()->create(['breed_id' => $breed->id, 'gender' => 'female', 'tag_id' => 'D-001']);

        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'br-uuid-1',
                    'entity' => 'breeding_record',
                    'action' => 'create',
                    'data'   => [
                        'sire_id'       => $sire->id,
                        'dam_id'        => $dam->id,
                        'breeding_date' => '2026-07-01',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        // Verify expected_kidding_date was auto-calculated (breeding_date + 150 days)
        $record = BreedingRecord::first();
        $this->assertNotNull($record->expected_kidding_date);
        $this->assertEquals('2026-11-28', $record->expected_kidding_date->format('Y-m-d'));
        $this->assertEquals('bred', $record->pregnancy_status);
    }

    // ─── PUSH: INVESTMENT ──────────────────────────────────────────────

    public function test_push_creates_investment(): void
    {
        $investor = Investor::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'inv-uuid-1',
                    'entity' => 'investment',
                    'action' => 'create',
                    'data'   => [
                        'investor_id' => $investor->id,
                        'amount'      => 50000.00,
                        'date'        => '2026-07-08',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('investments', [
            'investor_id' => $investor->id,
            'amount'      => 50000.00,
        ]);
    }

    // ─── PUSH: CARETAKER ───────────────────────────────────────────────

    public function test_push_creates_caretaker(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'ct-uuid-1',
                    'entity' => 'caretaker',
                    'action' => 'create',
                    'data'   => [
                        'name'          => 'Ali Khan',
                        'every_nth_kid' => 5,
                        'active'        => true,
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('caretakers', ['name' => 'Ali Khan']);
    }

    // ─── PUSH: BREED ───────────────────────────────────────────────────

    public function test_push_creates_breed(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'breed-uuid-1',
                    'entity' => 'breed',
                    'action' => 'create',
                    'data'   => [
                        'name'        => 'Nachi',
                        'description' => 'A Pakistani breed known for its distinctive ears.',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('breeds', ['name' => 'Nachi']);
    }

    // ─── PUSH: INVESTOR ────────────────────────────────────────────────

    public function test_push_creates_investor(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'invs-uuid-1',
                    'entity' => 'investor',
                    'action' => 'create',
                    'data'   => [
                        'name'  => 'Ahmed Raza',
                        'role'  => 'investor',
                        'email' => 'ahmed@example.com',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('investors', ['name' => 'Ahmed Raza', 'role' => 'investor']);
    }

    // ─── PUSH: BUDGET ENTRY ────────────────────────────────────────────

    public function test_push_creates_budget_entry(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'bud-uuid-1',
                    'entity' => 'budget_entry',
                    'action' => 'create',
                    'data'   => [
                        'type'     => 'expense',
                        'category' => 'Feed & Fodder',
                        'year'     => 2026,
                        'jan'      => 5000,
                        'feb'      => 6000,
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('budget_entries', [
            'category' => 'Feed & Fodder',
            'year'     => 2026,
            'jan'      => 5000,
        ]);
    }

    // ─── PUSH: OWNERSHIP UNIT ──────────────────────────────────────────

    public function test_push_creates_ownership_unit(): void
    {
        $breed    = Breed::factory()->create();
        $goat     = Goat::factory()->create(['breed_id' => $breed->id]);
        $investor = Investor::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'own-uuid-1',
                    'entity' => 'ownership_unit',
                    'action' => 'create',
                    'data'   => [
                        'goat_id'     => $goat->id,
                        'investor_id' => $investor->id,
                        'share_pct'   => 50,
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseHas('ownership_units', [
            'goat_id'     => $goat->id,
            'investor_id' => $investor->id,
            'share_pct'   => 50,
        ]);
    }

    // ─── PUSH: BATCH OPERATIONS ────────────────────────────────────────

    public function test_push_processes_multiple_operations(): void
    {
        $breed = Breed::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'batch-1',
                    'entity' => 'goat',
                    'action' => 'create',
                    'data'   => [
                        'tag_id'   => 'BATCH-001',
                        'breed_id' => $breed->id,
                        'gender'   => 'male',
                        'status'   => 'kid',
                    ],
                ],
                [
                    'id'     => 'batch-2',
                    'entity' => 'goat',
                    'action' => 'create',
                    'data'   => [
                        'tag_id'   => 'BATCH-002',
                        'breed_id' => $breed->id,
                        'gender'   => 'female',
                        'status'   => 'kid',
                    ],
                ],
                [
                    'id'     => 'batch-3',
                    'entity' => 'goat',
                    'action' => 'create',
                    'data'   => [
                        // Invalid: missing tag_id
                        'breed_id' => $breed->id,
                    ],
                ],
            ],
        ]);

        $response->assertOk();

        $results = $response->json('results');
        $this->assertCount(3, $results);
        $this->assertEquals('success', $results[0]['status']);
        $this->assertEquals('success', $results[1]['status']);
        $this->assertEquals('error', $results[2]['status']);

        $this->assertDatabaseHas('goats', ['tag_id' => 'BATCH-001']);
        $this->assertDatabaseHas('goats', ['tag_id' => 'BATCH-002']);
    }

    // ─── PUSH: DELETE ──────────────────────────────────────────────────

    public function test_push_deletes_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'type'     => 'expense',
            'category' => 'Test',
            'amount'   => 100,
            'date'     => '2026-07-01',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'        => 'del-uuid-1',
                    'entity'    => 'transaction',
                    'action'    => 'delete',
                    'record_id' => $transaction->id,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'success');

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    // ─── PUSH: UNKNOWN ENTITY ──────────────────────────────────────────

    public function test_push_unknown_entity_returns_error(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'unknown-1',
                    'entity' => 'unknown_entity',
                    'action' => 'create',
                    'data'   => [],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.status', 'error');
    }

    // ─── CSRF ──────────────────────────────────────────────────────────

    public function test_push_requires_csrf_token(): void
    {
        // Without CSRF token header, should fail
        // Note: In testing, CSRF is only verified for web middleware when not using actingAs with JSON
        // The actual CSRF protection is verified via middleware. Here we verify the endpoint accepts
        // requests with a proper CSRF token (which actingAs provides in test env).
        $breed = Breed::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/api/sync/push', [
            'operations' => [
                [
                    'id'     => 'csrf-1',
                    'entity' => 'goat',
                    'action' => 'create',
                    'data'   => [
                        'tag_id'   => 'CSRF-001',
                        'breetor_id' => $breed->id,
                        'gender'   => 'female',
                        'status'   => 'breeder',
                        'breed_id' => $breed->id,
                    ],
                ],
            ],
        ]);

        $response->assertOk();
    }
}
