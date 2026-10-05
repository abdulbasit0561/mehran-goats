<?php

namespace App\Services\Ai;

use App\Models\Goat;

/**
 * Assembles the data context sent to the AI advisor.
 * Caps record counts to keep prompts bounded for large herds.
 */
class AiHerdDataCollector
{
    public const MAX_HEALTH_RECORDS = 50;

    public const MAX_WEIGHT_LOGS = 12;

    public function goatContext(Goat $goat): array
    {
        $goat->loadMissing(['breed', 'healthRecords', 'weightLogs']);

        $data = [
            'tag_id'    => $goat->tag_id,
            'name'      => $goat->name,
            'breed'     => $goat->breed?->name,
            'gender'    => $goat->gender,
            'dob'       => $goat->dob?->format('Y-m-d'),
            'age_months' => $goat->age_in_months,
            'status'    => $goat->status,
            'current_weight_kg' => $goat->weight_kg !== null ? (float) $goat->weight_kg : null,
            'purchase_date'  => $goat->purchase_date?->format('Y-m-d'),
            'purchase_weight_kg' => $goat->purchase_weight !== null ? (float) $goat->purchase_weight : null,
            'weight_history' => $goat->weightLogs()
                ->orderByDesc('logged_at')
                ->limit(self::MAX_WEIGHT_LOGS)
                ->get()
                ->map(fn ($log) => [
                    'date'  => $log->logged_at->format('Y-m-d'),
                    'kg'    => (float) $log->weight_kg,
                    'note'  => $log->notes,
                ])
                ->values()
                ->all(),
            'health_records' => $goat->healthRecords()
                ->orderByDesc('date')
                ->limit(self::MAX_HEALTH_RECORDS)
                ->get()
                ->map(fn ($r) => [
                    'type'         => $r->type,
                    'date'         => $r->date->format('Y-m-d'),
                    'cost'         => (float) $r->cost,
                    'symptoms'     => $r->symptoms,
                    'diagnosis'    => $r->diagnosis,
                    'medication'   => $r->medication,
                    'dosage'       => $r->dosage,
                    'vet_name'     => $r->vet_name,
                    'vaccine_name' => $r->vaccine_name,
                    'notes'        => $r->notes,
                ])
                ->values()
                ->all(),
        ];

        if ($goat->gender === 'female') {
            $pregnancy = $goat->activePregnancy();
            $data['breeding'] = [
                'active_pregnancy' => $pregnancy ? [
                    'breeding_date'       => $pregnancy->breeding_date->format('Y-m-d'),
                    'pregnancy_status'    => $pregnancy->pregnancy_status,
                    'expected_kidding_date' => $pregnancy->expected_kidding_date?->format('Y-m-d'),
                    'days_pregnant'       => $pregnancy->days_pregnant ?? null,
                ] : null,
                'total_birthed_records' => $goat->birthedRecords()->count(),
            ];
        }

        // Recent per-goat expenses relevant to care (vet medicine, feed)
        $data['recent_expenses'] = \App\Models\Transaction::expense()
            ->where('goat_id', $goat->id)
            ->whereIn('category', ['Veterinary & Medicine', 'Feed & Fodder'])
            ->orderByDesc('date')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'date'     => $t->date->format('Y-m-d'),
                'category' => $t->category,
                'amount'   => (float) $t->amount,
                'notes'    => $t->notes,
            ])
            ->values()
            ->all();

        return $data;
    }

    public function farmContext(): array
    {
        $herd = Goat::inHerd()->with('breed')->get();

        $data = [
            'today'        => now()->format('Y-m-d'),
            'herd_size'    => $herd->count(),
            'by_status'    => $herd->countBy('status')->all(),
            'by_gender'    => $herd->countBy('gender')->all(),
            'by_breed'     => $herd->countBy(fn ($g) => $g->breed?->name ?? 'Unknown')->all(),
            'recent_deaths' => \App\Models\DeathRecord::orderByDesc('death_date')
                ->limit(10)
                ->get()
                ->map(fn ($d) => [
                    'date'  => $d->death_date->format('Y-m-d'),
                    'cause' => $d->cause,
                ])
                ->values()
                ->all(),
        ];

        // Feed & vet spend, last 3 and 6 months (farm-wide)
        $spend = fn (string $category, int $months) => round((float) \App\Models\Transaction::expense()
            ->where('category', $category)
            ->where('date', '>=', now()->subMonths($months))
            ->sum('amount'), 2);

        $data['spend'] = [
            'feed_fodder' => [
                'last_3_months' => $spend('Feed & Fodder', 3),
                'last_6_months' => $spend('Feed & Fodder', 6),
            ],
            'veterinary_medicine' => [
                'last_3_months' => $spend('Veterinary & Medicine', 3),
                'last_6_months' => $spend('Veterinary & Medicine', 6),
            ],
        ];

        // Vaccination gaps: goats in herd with no vaccination in the last 12 months
        $data['vaccination_gaps'] = Goat::inHerd()
            ->whereDoesntHave('healthRecords', fn ($q) => $q->where('type', 'vaccination')->where('date', '>=', now()->subYear()))
            ->pluck('tag_id')
            ->all();

        return $data;
    }
}
