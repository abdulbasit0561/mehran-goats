<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\DeathRecord;
use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeathRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
        $this->actingAs($this->user);

        $this->goat = Goat::create([
            'tag_id'   => 'TAG-001',
            'breed_id' => $this->breed->id,
            'gender'   => 'female',
            'status'   => 'breeder',
        ]);
    }

    public function test_declare_dead_form_loads_for_live_goat(): void
    {
        $response = $this->get("/goats/{$this->goat->id}/declare-dead");

        $response->assertStatus(200);
        $response->assertSee('Declare Dead');
        $response->assertSee('TAG-001');
    }

    public function test_declare_dead_form_redirects_when_already_deceased(): void
    {
        $this->goat->update(['status' => 'deceased']);

        $response = $this->get("/goats/{$this->goat->id}/declare-dead");

        $response->assertRedirect("/goats/{$this->goat->id}");
        $response->assertSessionHas('error');
    }

    public function test_can_declare_a_goat_dead_and_create_death_record(): void
    {
        $response = $this->post("/goats/{$this->goat->id}/declare-dead", [
            'death_date'      => '2026-07-15',
            'cause'           => 'Illness',
            'disposal_method' => 'buried',
            'disposal_value'  => 0,
            'notes'           => 'Found unresponsive in pen.',
        ]);

        $response->assertRedirect("/goats/{$this->goat->id}");
        $response->assertSessionHas('success');

        // Status flipped
        $this->assertDatabaseHas('goats', [
            'id'     => $this->goat->id,
            'status' => 'deceased',
        ]);

        // Death record created with all fields
        $this->assertDatabaseHas('death_records', [
            'goat_id'         => $this->goat->id,
            'death_date'      => '2026-07-15',
            'cause'           => 'Illness',
            'disposal_method' => 'buried',
            'disposal_value'  => 0,
            'notes'           => 'Found unresponsive in pen.',
        ]);
    }

    public function test_declare_dead_with_disposal_to_butcher(): void
    {
        $response = $this->post("/goats/{$this->goat->id}/declare-dead", [
            'death_date'      => '2026-07-15',
            'cause'           => 'Injury',
            'disposal_method' => 'sold_to_butcher',
            'disposal_value'  => 1500.00,
            'notes'           => null,
        ]);

        $response->assertRedirect("/goats/{$this->goat->id}");

        $this->assertDatabaseHas('death_records', [
            'goat_id'         => $this->goat->id,
            'disposal_method' => 'sold_to_butcher',
            'disposal_value'  => 1500.00,
        ]);
    }

    public function test_declaring_an_already_deceased_goat_is_blocked(): void
    {
        $this->goat->update(['status' => 'deceased']);
        DeathRecord::create([
            'goat_id'         => $this->goat->id,
            'death_date'      => '2026-07-10',
            'cause'           => 'Old age',
            'disposal_method' => 'buried',
            'disposal_value'  => 0,
        ]);

        $response = $this->post("/goats/{$this->goat->id}/declare-dead", [
            'death_date'      => '2026-07-15',
            'disposal_method' => 'buried',
        ]);

        $response->assertRedirect("/goats/{$this->goat->id}");
        $response->assertSessionHas('error');

        // Only one death record should exist (the original)
        $this->assertEquals(1, DeathRecord::where('goat_id', $this->goat->id)->count());
    }

    public function test_death_date_is_required(): void
    {
        $response = $this->post("/goats/{$this->goat->id}/declare-dead", [
            'disposal_method' => 'buried',
        ]);

        $response->assertSessionHasErrors('death_date');
    }

    public function test_death_date_cannot_be_in_the_future(): void
    {
        $future = now()->addDay()->format('Y-m-d');

        $response = $this->post("/goats/{$this->goat->id}/declare-dead", [
            'death_date'      => $future,
            'disposal_method' => 'buried',
        ]);

        $response->assertSessionHasErrors('death_date');
    }

    public function test_disposal_method_must_be_valid_enum(): void
    {
        $response = $this->post("/goats/{$this->goat->id}/declare-dead", [
            'death_date'      => '2026-07-15',
            'disposal_method' => 'dumped', // invalid
        ]);

        $response->assertSessionHasErrors('disposal_method');
    }

    public function test_show_page_displays_death_record(): void
    {
        $this->post("/goats/{$this->goat->id}/declare-dead", [
            'death_date'      => '2026-07-15',
            'cause'           => 'Predator attack',
            'disposal_method' => 'composted',
            'disposal_value'  => 0,
            'notes'           => 'Lost to wolves.',
        ]);

        $response = $this->get("/goats/{$this->goat->id}");

        $response->assertStatus(200);
        $response->assertSee('Death Record');
        $response->assertSee('Predator attack');
        $response->assertSee('composted');
        $response->assertSee('Lost to wolves.');
    }

    public function test_declare_dead_button_hidden_for_deceased_goat(): void
    {
        // Live goat shows the button
        $live = $this->get("/goats/{$this->goat->id}");
        $live->assertSee('Declare Dead');

        // Deceased goat does not
        $this->goat->update(['status' => 'deceased']);
        $dead = $this->get("/goats/{$this->goat->id}");
        $dead->assertDontSee('Declare Dead');
    }
}
