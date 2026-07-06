<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\Caretaker;
use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreedingKiddingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Beetal', 'description' => 'Punjab dual-purpose']);
        $this->actingAs($this->user);

        $this->sire = Goat::create([
            'tag_id' => 'BUCK-01', 'breed_id' => $this->breed->id,
            'gender' => 'male', 'status' => 'breeder',
        ]);
        $this->dam = Goat::create([
            'tag_id' => 'DOE-01', 'breed_id' => $this->breed->id,
            'gender' => 'female', 'status' => 'breeder',
        ]);
    }

    //------------------------------------------------------------------
    // Gestation / expected kidding date calculation
    //------------------------------------------------------------------

    public function test_expected_kidding_date_is_150_days_after_breeding(): void
    {
        $record = BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'litter_size'   => 0,
            'pregnancy_status' => 'bred',
        ]);

        // Jan 1 + 150 days = May 31
        $this->assertEquals('2026-05-31', $record->expected_kidding_date->format('Y-m-d'));
    }

    public function test_expected_kidding_date_for_december_breeding(): void
    {
        // Autumn breeding season: Dec 1 + 150 days = ~May 1
        $record = BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-12-01',
            'pregnancy_status' => 'bred',
        ]);

        $this->assertEquals('2027-04-30', $record->expected_kidding_date->format('Y-m-d'));
    }

    //------------------------------------------------------------------
    // Pregnancy status lifecycle
    //------------------------------------------------------------------

    public function test_new_breeding_record_defaults_to_bred_status(): void
    {
        $record = BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-06-01',
        ]);

        // DB default is 'bred'; refresh to get it since Eloquent model doesn't reflect DB defaults on insert
        $record->refresh();
        $this->assertEquals('bred', $record->pregnancy_status);
        $this->assertTrue($record->isPregnant());
        $this->assertFalse($record->isConfirmed());
    }

    public function test_can_mark_pregnant(): void
    {
        $record = BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-06-01',
        ]);

        $response = $this->post("/breeding-records/{$record->id}/mark-pregnant");

        $record->refresh();
        $this->assertEquals('pregnant', $record->pregnancy_status);
        $this->assertTrue($record->isConfirmed());
    }

    public function test_can_mark_open(): void
    {
        $record = BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-06-01',
            'pregnancy_status' => 'bred',
        ]);

        $this->post("/breeding-records/{$record->id}/mark-open");

        $record->refresh();
        $this->assertEquals('open', $record->pregnancy_status);
        $this->assertFalse($record->isPregnant());
    }

    public function test_days_pregnant_calculates_from_breeding_date(): void
    {
        $record = BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => now()->subDays(75)->format('Y-m-d'),
            'pregnancy_status' => 'bred',
        ]);

        $this->assertGreaterThan(70, $record->days_pregnant);
        $this->assertLessThan(80, $record->days_pregnant);
    }

    public function test_pregnancy_progress_is_between_0_and_100(): void
    {
        $record = BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => now()->subDays(75)->format('Y-m-d'),
            'pregnancy_status' => 'bred',
        ]);

        $this->assertGreaterThan(0, $record->pregnancy_progress);
        $this->assertLessThanOrEqual(100, $record->pregnancy_progress);
    }

    //------------------------------------------------------------------
    // Record Kidding — auto-creates kid goat records
    //------------------------------------------------------------------

    public function test_record_kidding_creates_kid_goats(): void
    {
        $record = BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'pregnancy_status' => 'bred',
        ]);

        $response = $this->post("/breeding-records/{$record->id}/kidding", [
            'kidding_date'       => '2026-06-01',
            'litter_size'        => 2,
            'kidding_difficulty' => 'easy',
            'kids' => [
                ['tag_id' => 'DOE-01-K1', 'gender' => 'female', 'weight_kg' => 3.5],
                ['tag_id' => 'DOE-01-K2', 'gender' => 'male', 'weight_kg' => 4.0],
            ],
        ]);

        $response->assertRedirect();

        // Kids should be created
        $this->assertDatabaseHas('goats', [
            'tag_id' => 'DOE-01-K1',
            'gender' => 'female',
            'sire_id' => $this->sire->id,
            'dam_id' => $this->dam->id,
            'birth_type' => 'farm_born',
            'status' => 'kid',
        ]);
        $this->assertDatabaseHas('goats', [
            'tag_id' => 'DOE-01-K2',
            'gender' => 'male',
            'birth_type' => 'farm_born',
        ]);

        // Breeding record should be updated
        $record->refresh();
        $this->assertEquals('kidded', $record->pregnancy_status);
        $this->assertEquals(2, $record->litter_size);
        $this->assertEquals(2, $record->kids()->count());
    }

    public function test_kidding_assigns_caretaker_on_nth_kid(): void
    {
        // Set up caretaker with every 5th kid
        $caretaker = Caretaker::create(['name' => 'Ali', 'every_nth_kid' => 5, 'active' => true]);

        // Previous kidding: 4 kids born (total count = 4)
        $record1 = BreedingRecord::create([
            'sire_id' => $this->sire->id, 'dam_id' => $this->dam->id,
            'breeding_date' => '2025-06-01',
            'kidding_date' => '2025-11-01',
            'litter_size' => 4,
            'pregnancy_status' => 'kidded',
        ]);

        // New kidding: 2 kids → global kids #5 and #6 → kid #5 belongs to caretaker
        $record2 = BreedingRecord::create([
            'sire_id' => $this->sire->id, 'dam_id' => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'pregnancy_status' => 'bred',
        ]);

        $this->post("/breeding-records/{$record2->id}/kidding", [
            'kidding_date' => '2026-06-01',
            'litter_size' => 2,
            'kids' => [
                ['tag_id' => 'DOE-01-K5', 'gender' => 'female'],
                ['tag_id' => 'DOE-01-K6', 'gender' => 'male'],
            ],
        ]);

        // Kid #5 should be assigned to caretaker, kid #6 should not
        $this->assertDatabaseHas('goats', [
            'tag_id' => 'DOE-01-K5',
            'caretaker_id' => $caretaker->id,
        ]);
        $this->assertDatabaseHas('goats', [
            'tag_id' => 'DOE-01-K6',
            'caretaker_id' => null,
        ]);
    }

    public function test_kidding_validates_litter_matches_kids_array(): void
    {
        $record = BreedingRecord::create([
            'sire_id' => $this->sire->id, 'dam_id' => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'pregnancy_status' => 'bred',
        ]);

        $response = $this->post("/breeding-records/{$record->id}/kidding", [
            'kidding_date' => '2026-06-01',
            'litter_size' => 3,
            'kids' => [
                ['tag_id' => 'DOE-01-K1', 'gender' => 'female'], // only 1 but says 3
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    //------------------------------------------------------------------
    // Goat lineage
    //------------------------------------------------------------------

    public function test_goat_has_sire_and_dam_relationships(): void
    {
        $kid = Goat::create([
            'tag_id' => 'KID-01', 'breed_id' => $this->breed->id,
            'gender' => 'female', 'status' => 'kid',
            'sire_id' => $this->sire->id,
            'dam_id' => $this->dam->id,
            'birth_type' => 'farm_born',
        ]);

        $this->assertEquals($this->sire->id, $kid->sire->id);
        $this->assertEquals($this->dam->id, $kid->dam->id);
        $this->assertTrue($kid->isFarmBorn());
    }

    public function test_doe_offspring_relationship(): void
    {
        Goat::create([
            'tag_id' => 'KID-A', 'breed_id' => $this->breed->id,
            'gender' => 'female', 'status' => 'kid',
            'dam_id' => $this->dam->id, 'birth_type' => 'farm_born',
        ]);
        Goat::create([
            'tag_id' => 'KID-B', 'breed_id' => $this->breed->id,
            'gender' => 'male', 'status' => 'kid',
            'dam_id' => $this->dam->id, 'birth_type' => 'farm_born',
        ]);

        $this->assertCount(2, $this->dam->offspring);
    }

    public function test_goat_active_pregnancy_returns_current_record(): void
    {
        BreedingRecord::create([
            'sire_id' => $this->sire->id, 'dam_id' => $this->dam->id,
            'breeding_date' => '2025-01-01',
            'kidding_date' => '2025-06-01',
            'litter_size' => 2,
            'pregnancy_status' => 'kidded',
        ]);
        $active = BreedingRecord::create([
            'sire_id' => $this->sire->id, 'dam_id' => $this->dam->id,
            'breeding_date' => '2026-06-01',
            'pregnancy_status' => 'pregnant',
        ]);

        $pregnancy = $this->dam->activePregnancy();
        $this->assertNotNull($pregnancy);
        $this->assertEquals($active->id, $pregnancy->id);
    }

    //------------------------------------------------------------------
    // Breeding dashboard
    //------------------------------------------------------------------

    public function test_breeding_dashboard_loads(): void
    {
        $response = $this->get('/breeding');

        $response->assertStatus(200);
        $response->assertSee('Breeding');
        $response->assertSee('Pregnant Does');
        $response->assertSee('Kidding Rate');
    }

    public function test_breeding_dashboard_shows_pregnant_count(): void
    {
        BreedingRecord::create([
            'sire_id' => $this->sire->id, 'dam_id' => $this->dam->id,
            'breeding_date' => '2026-06-01',
            'pregnancy_status' => 'pregnant',
        ]);

        $response = $this->get('/breeding');

        $response->assertSee('Pregnant Does');
    }

    //------------------------------------------------------------------
    // Breeding record editing
    //------------------------------------------------------------------

    public function test_can_edit_breeding_record(): void
    {
        $record = BreedingRecord::create([
            'sire_id' => $this->sire->id, 'dam_id' => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'pregnancy_status' => 'bred',
        ]);

        $response = $this->get("/breeding-records/{$record->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('Edit Breeding Record');
    }

    public function test_can_update_breeding_record(): void
    {
        $record = BreedingRecord::create([
            'sire_id' => $this->sire->id, 'dam_id' => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'pregnancy_status' => 'bred',
        ]);

        $this->put("/breeding-records/{$record->id}", [
            'sire_id' => $this->sire->id,
            'dam_id' => $this->dam->id,
            'breeding_date' => '2026-02-01',
            'litter_size' => 0,
            'notes' => 'Updated note',
        ]);

        $record->refresh();
        $this->assertEquals('2026-02-01', $record->breeding_date->format('Y-m-d'));
        $this->assertEquals('Updated note', $record->notes);
        // Expected kidding date: Feb 1 + 150 days = Jul 1
        $this->assertEquals('2026-07-01', $record->expected_kidding_date->format('Y-m-d'));
    }

    //------------------------------------------------------------------
    // Kidding difficulty
    //------------------------------------------------------------------

    public function test_kidding_difficulty_is_saved(): void
    {
        $record = BreedingRecord::create([
            'sire_id' => $this->sire->id, 'dam_id' => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'pregnancy_status' => 'bred',
        ]);

        $this->post("/breeding-records/{$record->id}/kidding", [
            'kidding_date' => '2026-06-01',
            'litter_size' => 1,
            'kidding_difficulty' => 'assisted',
            'kids' => [
                ['tag_id' => 'DOE-01-K1', 'gender' => 'female'],
            ],
        ]);

        $record->refresh();
        $this->assertEquals('assisted', $record->kidding_difficulty);
    }
}
