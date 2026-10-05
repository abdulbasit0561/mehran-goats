<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthWeightFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
        $this->goat = Goat::create([
            'tag_id'    => 'TAG-HW-001',
            'breed_id'  => $this->breed->id,
            'gender'    => 'female',
            'status'    => 'grower',
            'weight_kg' => 30,
        ]);
        $this->actingAs($this->user);
    }

    /* ---------- Weight logs ---------- */

    public function test_weight_log_can_be_recorded_from_goat_page(): void
    {
        $response = $this->post(route('goats.weight-logs.store', $this->goat), [
            'weight_kg' => 34.5,
            'logged_at' => now()->format('Y-m-d'),
            'notes'     => 'Monthly checkup',
        ]);

        $response->assertRedirect(route('goats.show', $this->goat));
        $this->assertDatabaseHas('weight_logs', [
            'goat_id'   => $this->goat->id,
            'weight_kg' => 34.5,
            'notes'     => 'Monthly checkup',
        ]);
    }

    public function test_recording_weight_updates_goat_current_weight_snapshot(): void
    {
        $this->post(route('goats.weight-logs.store', $this->goat), [
            'weight_kg' => 34.5,
            'logged_at' => now()->format('Y-m-d'),
            'notes'     => null,
        ]);

        $this->goat->refresh();
        $this->assertEquals(34.5, (float) $this->goat->weight_kg);
    }

    public function test_goat_page_shows_weight_history(): void
    {
        $this->goat->weightLogs()->createMany([
            ['weight_kg' => 30, 'logged_at' => now()->subMonths(2)->format('Y-m-d')],
            ['weight_kg' => 33, 'logged_at' => now()->subMonth()->format('Y-m-d')],
            ['weight_kg' => 35, 'logged_at' => now()->format('Y-m-d')],
        ]);

        $response = $this->get(route('goats.show', $this->goat));

        $response->assertStatus(200);
        $response->assertSee('Weight History');
        $response->assertSee('35.00');
    }

    public function test_invalid_weight_is_rejected(): void
    {
        $this->post(route('goats.weight-logs.store', $this->goat), [
            'weight_kg' => 0,
            'logged_at' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('weight_kg');

        $this->post(route('goats.weight-logs.store', $this->goat), [
            'weight_kg' => -5,
            'logged_at' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('weight_kg');

        $this->post(route('goats.weight-logs.store', $this->goat), [
            'weight_kg' => 500,
            'logged_at' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('weight_kg');

        $this->assertDatabaseCount('weight_logs', 0);
    }

    public function test_duplicate_weight_same_day_is_allowed(): void
    {
        $today = now()->format('Y-m-d');
        $this->post(route('goats.weight-logs.store', $this->goat), ['weight_kg' => 30.5, 'logged_at' => $today]);
        $this->post(route('goats.weight-logs.store', $this->goat), ['weight_kg' => 31, 'logged_at' => $today]);

        $this->assertDatabaseCount('weight_logs', 2);
        $this->assertEquals(31, (float) $this->goat->refresh()->weight_kg);
    }

    public function test_deleting_weight_log_recalculates_current_weight(): void
    {
        $a = $this->goat->weightLogs()->create(['weight_kg' => 30, 'logged_at' => now()->subMonth()->format('Y-m-d')]);
        $b = $this->goat->weightLogs()->create(['weight_kg' => 36, 'logged_at' => now()->format('Y-m-d')]);
        $this->assertEquals(36, (float) $this->goat->refresh()->weight_kg);

        $this->delete(route('goats.weight-logs.destroy', $b));

        $this->assertDatabaseMissing('weight_logs', ['id' => $b->id]);
        $this->assertEquals(30, (float) $this->goat->refresh()->weight_kg);
    }

    /* ---------- Health record clinical fields ---------- */

    public function test_health_record_can_include_clinical_fields(): void
    {
        $response = $this->post(route('goats.health-records.store', $this->goat), [
            'type'        => 'treatment',
            'date'        => now()->format('Y-m-d'),
            'cost'        => 500,
            'symptoms'    => 'Coughing, nasal discharge',
            'diagnosis'   => 'Pneumonia',
            'medication'  => 'Oxytetracycline',
            'dosage'      => '1ml per 10kg',
            'vet_name'    => 'Dr. Ali',
            'notes'       => 'Follow-up in 7 days',
        ]);

        $response->assertRedirect(route('goats.show', $this->goat));
        $this->assertDatabaseHas('health_records', [
            'goat_id'    => $this->goat->id,
            'type'       => 'treatment',
            'symptoms'   => 'Coughing, nasal discharge',
            'diagnosis'  => 'Pneumonia',
            'medication' => 'Oxytetracycline',
            'dosage'     => '1ml per 10kg',
            'vet_name'   => 'Dr. Ali',
        ]);
    }

    public function test_vaccination_record_can_include_vaccine_name(): void
    {
        $this->post(route('goats.health-records.store', $this->goat), [
            'type'         => 'vaccination',
            'date'         => now()->format('Y-m-d'),
            'cost'         => 100,
            'vaccine_name' => 'Enterotoxaemia (CDT)',
        ]);

        $this->assertDatabaseHas('health_records', [
            'goat_id'      => $this->goat->id,
            'type'         => 'vaccination',
            'vaccine_name' => 'Enterotoxaemia (CDT)',
        ]);
    }

    public function test_goat_page_shows_clinical_fields_on_health_record(): void
    {
        $this->goat->healthRecords()->create([
            'type'       => 'treatment',
            'date'       => now()->format('Y-m-d'),
            'cost'       => 500,
            'symptoms'   => 'Coughing',
            'diagnosis'  => 'Pneumonia',
            'medication' => 'Oxytetracycline',
            'dosage'     => '1ml per 10kg',
            'vet_name'   => 'Dr. Ali',
            'notes'      => 'Follow-up in 7 days',
        ]);

        $response = $this->get(route('goats.show', $this->goat));

        $response->assertStatus(200);
        $response->assertSee('Pneumonia');
        $response->assertSee('Oxytetracycline');
        $response->assertSee('Dr. Ali');
    }

    public function test_legacy_health_records_still_display(): void
    {
        // Simulate a legacy record with only type/date/cost/notes
        $this->goat->healthRecords()->create([
            'type'  => 'checkup',
            'date'  => now()->subMonth()->format('Y-m-d'),
            'cost'  => 50,
            'notes' => 'Legacy free-text note',
        ]);

        $response = $this->get(route('goats.show', $this->goat));

        $response->assertStatus(200);
        $response->assertSee('Legacy free-text note');
        $response->assertSee('checkup');
    }

    public function test_health_record_form_includes_new_fields(): void
    {
        $response = $this->get(route('goats.health-records.create', $this->goat));

        $response->assertStatus(200);
        $response->assertSee('Symptoms');
        $response->assertSee('Diagnosis');
        $response->assertSee('Medication');
        $response->assertSee('Vet name');
        $response->assertSee('Vaccine name');
    }
}
