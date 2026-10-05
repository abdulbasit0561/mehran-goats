<?php

namespace Tests\Unit;

use App\Models\Breed;
use App\Models\Goat;
use App\Models\HealthRecord;
use App\Models\WeightLog;
use App\Services\Ai\RuleBasedAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleBasedAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private RuleBasedAlertService $service;

    private Breed $breed;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RuleBasedAlertService;
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
    }

    private function goat(array $overrides = []): Goat
    {
        return Goat::create(array_merge([
            'tag_id'   => 'TAG-AL-'.uniqid(),
            'breed_id' => $this->breed->id,
            'gender'   => 'female',
            'status'   => 'breeder',
        ], $overrides));
    }

    public function test_goat_with_no_vaccination_in_12_months_is_flagged(): void
    {
        $goat = $this->goat();
        $goat->healthRecords()->create([
            'type' => 'vaccination', 'date' => now()->subMonths(14)->format('Y-m-d'), 'cost' => 50,
        ]);

        $alerts = $this->service->alerts();

        $this->assertTrue($alerts->contains(fn ($a) => $a['rule'] === 'vaccination_overdue' && $a['tag_id'] === $goat->tag_id));
    }

    public function test_recently_vaccinated_goat_is_not_flagged(): void
    {
        $goat = $this->goat();
        $goat->healthRecords()->create([
            'type' => 'vaccination', 'date' => now()->subMonths(3)->format('Y-m-d'), 'cost' => 50,
        ]);

        $alerts = $this->service->alerts();

        $this->assertFalse($alerts->contains(fn ($a) => $a['tag_id'] === $goat->tag_id && $a['rule'] === 'vaccination_overdue'));
    }

    public function test_weight_drop_over_10_percent_is_flagged(): void
    {
        $goat = $this->goat();
        $goat->weightLogs()->createMany([
            ['weight_kg' => 40, 'logged_at' => now()->subMonth()->format('Y-m-d')],
            ['weight_kg' => 34, 'logged_at' => now()->format('Y-m-d')],
        ]);

        $alerts = $this->service->alerts();

        $this->assertTrue($alerts->contains(fn ($a) => $a['rule'] === 'weight_drop' && $a['tag_id'] === $goat->tag_id));
    }

    public function test_small_weight_change_is_not_flagged(): void
    {
        $goat = $this->goat();
        $goat->weightLogs()->createMany([
            ['weight_kg' => 40, 'logged_at' => now()->subMonth()->format('Y-m-d')],
            ['weight_kg' => 39, 'logged_at' => now()->format('Y-m-d')],
        ]);

        $alerts = $this->service->alerts();

        $this->assertFalse($alerts->contains(fn ($a) => $a['rule'] === 'weight_drop' && $a['tag_id'] === $goat->tag_id));
    }

    public function test_goat_with_one_weight_log_only_is_not_flagged(): void
    {
        $goat = $this->goat();
        $goat->weightLogs()->create(['weight_kg' => 34, 'logged_at' => now()->format('Y-m-d')]);

        $alerts = $this->service->alerts();

        $this->assertFalse($alerts->contains(fn ($a) => $a['rule'] === 'weight_drop'));
    }

    public function test_sold_and_deceased_goats_are_excluded(): void
    {
        $sold = $this->goat(['status' => 'sold', 'tag_id' => 'TAG-AL-SOLD']);
        $sold->weightLogs()->createMany([
            ['weight_kg' => 40, 'logged_at' => now()->subMonth()->format('Y-m-d')],
            ['weight_kg' => 30, 'logged_at' => now()->format('Y-m-d')],
        ]);

        $alerts = $this->service->alerts();

        $this->assertFalse($alerts->contains(fn ($a) => $a['tag_id'] === 'TAG-AL-SOLD'));
    }

    public function test_max_one_alert_per_goat_per_rule(): void
    {
        $goat = $this->goat();
        $goat->weightLogs()->createMany([
            ['weight_kg' => 40, 'logged_at' => now()->subMonths(2)->format('Y-m-d')],
            ['weight_kg' => 35, 'logged_at' => now()->subMonth()->format('Y-m-d')],
            ['weight_kg' => 30, 'logged_at' => now()->format('Y-m-d')],
        ]);

        $count = $this->service->alerts()
            ->where('tag_id', $goat->tag_id)
            ->where('rule', 'weight_drop')
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_pregnancy_past_expected_date_is_flagged(): void
    {
        [$doe, $buck] = $this->doeAndBuck();
        $doe->birthedRecords()->create([
            'sire_id'               => $buck->id,
            'breeding_date'         => now()->subMonths(6)->format('Y-m-d'),
            'pregnancy_status'      => 'pregnant',
            'expected_kidding_date' => now()->subDays(20)->format('Y-m-d'),
        ]);

        $alerts = $this->service->alerts();

        $this->assertTrue($alerts->contains(fn ($a) => $a['rule'] === 'pregnancy_overdue' && $a['tag_id'] === $doe->tag_id));
    }

    public function test_pregnancy_within_grace_window_is_not_flagged(): void
    {
        [$doe, $buck] = $this->doeAndBuck();
        $doe->birthedRecords()->create([
            'sire_id'               => $buck->id,
            'breeding_date'         => now()->subMonths(5)->format('Y-m-d'),
            'pregnancy_status'      => 'pregnant',
            'expected_kidding_date' => now()->subDays(2)->format('Y-m-d'),
        ]);

        $alerts = $this->service->alerts();

        $this->assertFalse($alerts->contains(fn ($a) => $a['rule'] === 'pregnancy_overdue'));
    }

    public function test_kidded_record_is_not_flagged(): void
    {
        [$doe, $buck] = $this->doeAndBuck();
        $doe->birthedRecords()->create([
            'sire_id'               => $buck->id,
            'breeding_date'         => now()->subMonths(6)->format('Y-m-d'),
            'pregnancy_status'      => 'kidded',
            'expected_kidding_date' => now()->subDays(20)->format('Y-m-d'),
        ]);

        $alerts = $this->service->alerts();

        $this->assertFalse($alerts->contains(fn ($a) => $a['rule'] === 'pregnancy_overdue'));
    }

    private function doeAndBuck(): array
    {
        $doe = $this->goat(['tag_id' => 'TAG-AL-DOE-'.uniqid()]);
        $buck = $this->goat(['tag_id' => 'TAG-AL-BUCK-'.uniqid(), 'gender' => 'male']);

        return [$doe, $buck];
    }
}
