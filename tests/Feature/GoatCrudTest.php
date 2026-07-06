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
