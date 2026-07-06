<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreedManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // ---- Index ----

    public function test_can_list_breeds(): void
    {
        Breed::create(['name' => 'Boer']);
        Breed::create(['name' => 'Beetal']);

        $response = $this->get('/breeds');

        $response->assertStatus(200);
        $response->assertSee('Boer');
        $response->assertSee('Beetal');
    }

    public function test_breeds_require_auth(): void
    {
        auth()->logout();

        $response = $this->get('/breeds');

        $response->assertRedirect('/login');
    }

    public function test_empty_breed_list_shows_empty_state(): void
    {
        $response = $this->get('/breeds');

        $response->assertStatus(200);
        $response->assertSee('No breeds yet');
    }

    // ---- Store ----

    public function test_can_create_a_breed(): void
    {
        $response = $this->post('/breeds', [
            'name'        => 'Kamori',
            'description' => 'A popular dairy breed from Sindh',
        ]);

        $response->assertRedirect('/breeds');
        $this->assertDatabaseHas('breeds', ['name' => 'Kamori']);
    }

    public function test_duplicate_breed_name_is_rejected(): void
    {
        Breed::create(['name' => 'Boer']);

        $response = $this->post('/breeds', [
            'name' => 'Boer',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_breed_name_is_required(): void
    {
        $response = $this->post('/breeds', [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_description_is_optional(): void
    {
        $response = $this->post('/breeds', [
            'name' => 'Sirohi',
        ]);

        $response->assertRedirect('/breeds');
        $this->assertDatabaseHas('breeds', ['name' => 'Sirohi', 'description' => null]);
    }

    // ---- Update ----

    public function test_can_edit_a_breed(): void
    {
        $breed = Breed::create(['name' => 'Boer', 'description' => 'Old desc']);

        $response = $this->put("/breeds/{$breed->id}", [
            'name'        => 'Boer Improved',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect('/breeds');
        $this->assertDatabaseHas('breeds', [
            'id'          => $breed->id,
            'name'        => 'Boer Improved',
            'description' => 'Updated description',
        ]);
    }

    public function test_can_rename_without_unique_conflict_on_self(): void
    {
        $breed = Breed::create(['name' => 'Boer']);

        // Same name on the same record should NOT trigger unique error
        $response = $this->put("/breeds/{$breed->id}", [
            'name' => 'Boer',
        ]);

        $response->assertSessionHasNoErrors();
    }

    // ---- Destroy ----

    public function test_can_delete_a_breed_with_no_goats(): void
    {
        $breed = Breed::create(['name' => 'Beetal']);

        $response = $this->delete("/breeds/{$breed->id}");

        $response->assertRedirect('/breeds');
        $this->assertDatabaseMissing('breeds', ['id' => $breed->id]);
    }

    public function test_cannot_delete_a_breed_with_goats(): void
    {
        $breed = Breed::create(['name' => 'Boer']);
        Goat::create([
            'tag_id'   => 'G-001',
            'breed_id' => $breed->id,
            'gender'   => 'male',
            'status'   => 'kid',
        ]);

        $response = $this->delete("/breeds/{$breed->id}");

        $response->assertRedirect('/breeds');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('breeds', ['id' => $breed->id]);
    }

    // ---- Integration: new breed appears in goat form ----

    public function test_new_breed_appears_in_goat_create_dropdown(): void
    {
        Breed::create(['name' => 'Nachi']);

        $response = $this->get('/goats/create');

        $response->assertStatus(200);
        $response->assertSee('Nachi');
    }

    public function test_breed_list_shows_male_and_female_goat_counts(): void
    {
        $breed = Breed::create(['name' => 'Boer']);

        // 2 males + 1 female = 3 total goats
        Goat::create(['tag_id' => 'M1', 'breed_id' => $breed->id, 'gender' => 'male', 'status' => 'kid']);
        Goat::create(['tag_id' => 'M2', 'breed_id' => $breed->id, 'gender' => 'male', 'status' => 'kid']);
        Goat::create(['tag_id' => 'F1', 'breed_id' => $breed->id, 'gender' => 'female', 'status' => 'kid']);

        $response = $this->get('/breeds');

        $response->assertStatus(200);
        $response->assertSee('♂');
        $response->assertSee('♀');
        $response->assertSee('2 male');
        $response->assertSee('1 female');
    }
}
