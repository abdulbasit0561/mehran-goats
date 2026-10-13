<?php

namespace Tests\Unit;

use App\Models\Breed;
use App\Models\Goat;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Agent\FarmOperations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmOperationsTest extends TestCase
{
    use RefreshDatabase;

    private FarmOperations $operations;

    private Goat $goat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operations = new FarmOperations;
        $breed = Breed::create(['name' => 'Beetal', 'description' => 'Dairy']);
        $this->goat = Goat::create([
            'tag_id' => 'MG-12',
            'name' => 'Rani',
            'breed_id' => $breed->id,
            'gender' => 'female',
            'status' => 'breeder',
            'weight_kg' => 30,
        ]);
    }

    public function test_search_finds_a_goat_by_tag_fragment(): void
    {
        $result = $this->operations->run('search_goats', ['query' => 'mg-12']);

        $this->assertTrue($result['ok']);
        $this->assertSame('MG-12', $result['data'][0]['tag']);
        $this->assertSame('Rani', $result['data'][0]['name']);
    }

    public function test_record_weight_updates_the_goat(): void
    {
        $result = $this->operations->run('record_weight', [
            'tag' => 'mg-12',
            'weight_kg' => 32.5,
            'date' => 'today',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertDatabaseHas('weight_logs', [
            'goat_id' => $this->goat->id,
            'weight_kg' => 32.5,
        ]);
        $this->assertSame('32.50', $this->goat->fresh()->weight_kg);
    }

    public function test_record_expense_rejects_an_unknown_category(): void
    {
        $result = $this->operations->run('record_expense', [
            'category' => 'Bribes',
            'amount' => 100,
            'date' => now()->toDateString(),
        ]);

        $this->assertFalse($result['ok']);
        $this->assertSame(0, Transaction::count());
    }

    public function test_unknown_tool_does_not_throw(): void
    {
        $result = $this->operations->run('drop_database', []);

        $this->assertFalse($result['ok']);
    }
}
