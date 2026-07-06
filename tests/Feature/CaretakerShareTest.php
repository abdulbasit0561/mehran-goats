<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\Caretaker;
use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaretakerShareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
        $this->actingAs($this->user);

        // Create parent goats for breeding records
        $this->sire = Goat::create([
            'tag_id' => 'SIRE-01', 'breed_id' => $this->breed->id,
            'gender' => 'male', 'status' => 'breeder',
        ]);
        $this->dam = Goat::create([
            'tag_id' => 'DAM-01', 'breed_id' => $this->breed->id,
            'gender' => 'female', 'status' => 'breeder',
        ]);
    }

    //------------------------------------------------------------------
    // CRUD tests
    //------------------------------------------------------------------

    public function test_can_list_caretakers(): void
    {
        Caretaker::create(['name' => 'Ali', 'every_nth_kid' => 5]);

        $response = $this->get('/caretakers');

        $response->assertStatus(200);
        $response->assertSee('Ali');
        $response->assertSee('Caretaker Share System');
    }

    public function test_can_create_caretaker(): void
    {
        $response = $this->post('/caretakers', [
            'name'          => 'Rahim',
            'phone'         => '0300-1234567',
            'every_nth_kid' => 5,
            'active'        => true,
        ]);

        $response->assertRedirect('/caretakers');
        $this->assertDatabaseHas('caretakers', ['name' => 'Rahim', 'every_nth_kid' => 5]);
    }

    public function test_caretaker_defaults_to_every_5th_kid(): void
    {
        $response = $this->post('/caretakers', [
            'name'          => 'Default Test',
            'every_nth_kid' => 5,
        ]);

        $response->assertRedirect('/caretakers');
        $this->assertDatabaseHas('caretakers', ['name' => 'Default Test', 'every_nth_kid' => 5]);
    }

    public function test_can_update_caretaker(): void
    {
        $caretaker = Caretaker::create(['name' => 'Old Name', 'every_nth_kid' => 5]);

        $this->put("/caretakers/{$caretaker->id}", [
            'name'          => 'New Name',
            'every_nth_kid' => 3,
            'active'        => true,
        ]);

        $this->assertDatabaseHas('caretakers', [
            'id' => $caretaker->id,
            'name' => 'New Name',
            'every_nth_kid' => 3,
        ]);
    }

    public function test_can_delete_caretaker(): void
    {
        $caretaker = Caretaker::create(['name' => 'Delete Me', 'every_nth_kid' => 5]);

        $this->delete("/caretakers/{$caretaker->id}");

        $this->assertDatabaseMissing('caretakers', ['id' => $caretaker->id]);
    }

    public function test_deleting_caretaker_unassigns_goats(): void
    {
        $caretaker = Caretaker::create(['name' => 'Ali', 'every_nth_kid' => 5]);
        $goat = Goat::create([
            'tag_id' => 'KID-01', 'breed_id' => $this->breed->id,
            'caretaker_id' => $caretaker->id,
            'gender' => 'female', 'status' => 'kid',
        ]);

        $this->delete("/caretakers/{$caretaker->id}");

        $goat->refresh();
        $this->assertNull($goat->caretaker_id);
    }

    //------------------------------------------------------------------
    // Birth ledger calculation tests (the core logic)
    //------------------------------------------------------------------

    public function test_zero_births_means_zero_owed(): void
    {
        $caretaker = Caretaker::create(['name' => 'Test', 'every_nth_kid' => 5]);

        $this->assertSame(0, $caretaker->total_kids_born);
        $this->assertSame(0, $caretaker->kids_owed);
        $this->assertSame(0, $caretaker->kids_assigned);
        $this->assertSame(0, $caretaker->kids_pending);
    }

    public function test_four_kids_born_owes_zero_at_every_5th(): void
    {
        $caretaker = Caretaker::create(['name' => 'Test', 'every_nth_kid' => 5]);

        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'kidding_date'  => '2026-06-01',
            'litter_size'   => 4,
        ]);

        $this->assertSame(4, $caretaker->total_kids_born);
        $this->assertSame(0, $caretaker->kids_owed); // floor(4/5) = 0
        $this->assertSame(5, $caretaker->next_share_at_kid);
    }

    public function test_five_kids_born_owes_one_at_every_5th(): void
    {
        $caretaker = Caretaker::create(['name' => 'Test', 'every_nth_kid' => 5]);

        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'kidding_date'  => '2026-06-01',
            'litter_size'   => 5,
        ]);

        $this->assertSame(5, $caretaker->total_kids_born);
        $this->assertSame(1, $caretaker->kids_owed); // floor(5/5) = 1
        $this->assertSame(10, $caretaker->next_share_at_kid);
    }

    public function test_ten_kids_born_owes_two_at_every_5th(): void
    {
        $caretaker = Caretaker::create(['name' => 'Test', 'every_nth_kid' => 5]);

        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'kidding_date'  => '2026-06-01',
            'litter_size'   => 7,
        ]);
        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-02-01',
            'kidding_date'  => '2026-06-15',
            'litter_size'   => 3,
        ]);

        $this->assertSame(10, $caretaker->total_kids_born);
        $this->assertSame(2, $caretaker->kids_owed); // floor(10/5) = 2
    }

    public function test_every_3rd_kid_config(): void
    {
        $caretaker = Caretaker::create(['name' => 'Test', 'every_nth_kid' => 3]);

        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'kidding_date'  => '2026-06-01',
            'litter_size'   => 7,
        ]);

        $this->assertSame(7, $caretaker->total_kids_born);
        $this->assertSame(2, $caretaker->kids_owed); // floor(7/3) = 2
    }

    public function test_birth_ledger_counts_shares_per_litter(): void
    {
        $caretaker = Caretaker::create(['name' => 'Test', 'every_nth_kid' => 5]);

        // First kidding: 3 kids → kid numbers 1,2,3 → no 5th boundary
        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'kidding_date'  => '2026-05-01',
            'litter_size'   => 3,
        ]);
        // Second kidding: 4 kids → kid numbers 4,5,6,7 → 5th kid belongs to caretaker
        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-02-01',
            'kidding_date'  => '2026-06-01',
            'litter_size'   => 4,
        ]);

        $ledger = $caretaker->birthLedger();

        $this->assertCount(2, $ledger);
        $this->assertSame(0, $ledger[0]->share_count); // kids 1-3, no 5th
        $this->assertSame(1, $ledger[1]->share_count); // kids 4-7, one 5th
        $this->assertSame(1, $ledger[0]->kid_start);
        $this->assertSame(3, $ledger[0]->kid_end);
        $this->assertSame(4, $ledger[1]->kid_start);
        $this->assertSame(7, $ledger[1]->kid_end);
    }

    public function test_kids_pending_is_owed_minus_assigned(): void
    {
        $caretaker = Caretaker::create(['name' => 'Test', 'every_nth_kid' => 5]);

        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'kidding_date'  => '2026-06-01',
            'litter_size'   => 10, // 2 owed
        ]);

        // Assign one goat
        Goat::create([
            'tag_id' => 'KID-05', 'breed_id' => $this->breed->id,
            'caretaker_id' => $caretaker->id,
            'gender' => 'female', 'status' => 'kid',
        ]);

        $this->assertSame(2, $caretaker->kids_owed);
        $this->assertSame(1, $caretaker->kids_assigned);
        $this->assertSame(1, $caretaker->kids_pending);
    }

    public function test_kids_pending_never_negative(): void
    {
        $caretaker = Caretaker::create(['name' => 'Test', 'every_nth_kid' => 5]);

        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'kidding_date'  => '2026-06-01',
            'litter_size'   => 5, // 1 owed
        ]);

        // Assign 3 goats (more than owed — should not go negative)
        for ($i = 1; $i <= 3; $i++) {
            Goat::create([
                'tag_id' => "KID-0{$i}", 'breed_id' => $this->breed->id,
                'caretaker_id' => $caretaker->id,
                'gender' => 'female', 'status' => 'kid',
            ]);
        }

        $this->assertSame(1, $caretaker->kids_owed);
        $this->assertSame(3, $caretaker->kids_assigned);
        $this->assertSame(0, $caretaker->kids_pending); // max(0, 1-3) = 0
    }

    //------------------------------------------------------------------
    // Goat integration tests
    //------------------------------------------------------------------

    public function test_goat_can_be_assigned_to_caretaker(): void
    {
        $caretaker = Caretaker::create(['name' => 'Ali', 'every_nth_kid' => 5]);

        $response = $this->post('/goats', [
            'tag_id'       => 'KID-TEST',
            'breed_id'     => $this->breed->id,
            'caretaker_id' => $caretaker->id,
            'gender'       => 'male',
            'status'       => 'kid',
        ]);

        $response->assertRedirect('/goats');
        $this->assertDatabaseHas('goats', [
            'tag_id' => 'KID-TEST',
            'caretaker_id' => $caretaker->id,
        ]);
    }

    public function test_goat_can_be_updated_to_assign_caretaker(): void
    {
        $caretaker = Caretaker::create(['name' => 'Ali', 'every_nth_kid' => 5]);
        $goat = Goat::create([
            'tag_id' => 'KID-UP', 'breed_id' => $this->breed->id,
            'gender' => 'female', 'status' => 'kid',
        ]);

        $this->put("/goats/{$goat->id}", [
            'tag_id'       => 'KID-UP',
            'breed_id'     => $this->breed->id,
            'caretaker_id' => $caretaker->id,
            'gender'       => 'female',
            'status'       => 'kid',
        ]);

        $this->assertDatabaseHas('goats', [
            'id' => $goat->id,
            'caretaker_id' => $caretaker->id,
        ]);
    }

    public function test_caretaker_show_page_displays_ledger(): void
    {
        $caretaker = Caretaker::create(['name' => 'Ali', 'every_nth_kid' => 5]);

        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-01-01',
            'kidding_date'  => '2026-06-01',
            'litter_size'   => 5,
        ]);

        $response = $this->get("/caretakers/{$caretaker->id}");

        $response->assertStatus(200);
        $response->assertSee('Birth Ledger');
        $response->assertSee('+1 for caretaker');
        $response->assertSee('Owed to Caretaker');
    }

    public function test_breeding_records_without_kidding_date_excluded(): void
    {
        $caretaker = Caretaker::create(['name' => 'Test', 'every_nth_kid' => 5]);

        // Breeding record without kidding_date (pregnancy in progress)
        BreedingRecord::create([
            'sire_id'       => $this->sire->id,
            'dam_id'        => $this->dam->id,
            'breeding_date' => '2026-06-01',
            'kidding_date'  => null,
            'litter_size'   => 0,
        ]);

        $this->assertSame(0, $caretaker->total_kids_born);
        $this->assertSame(0, $caretaker->kids_owed);
    }
}
