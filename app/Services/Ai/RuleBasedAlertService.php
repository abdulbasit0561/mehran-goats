<?php

namespace App\Services\Ai;

use App\Models\Goat;
use Illuminate\Support\Collection;

/**
 * Deterministic, API-free alerts computed directly from herd data.
 * Complements the LLM report so critical issues surface even if the
 * AI service is unavailable. Max one alert per goat per rule.
 */
class RuleBasedAlertService
{
    /** @return Collection<int, array{rule: string, tag_id: string, title: string, detail: string, severity: string}> */
    public function alerts(): Collection
    {
        return collect()
            ->merge($this->vaccinationOverdue())
            ->merge($this->weightDrops())
            ->merge($this->overduePregnancies())
            ->values();
    }

    /**
     * Female breeders whose active pregnancy is past the expected kidding
     * date (7-day grace window) without a recorded kidding.
     */
    private function overduePregnancies(): array
    {
        $alerts = [];

        $goats = Goat::inHerd()->where('gender', 'female')->with('birthedRecords')->get();

        foreach ($goats as $goat) {
            $pregnancy = $goat->birthedRecords
                ->first(fn ($b) => $b->expected_kidding_date
                    && in_array($b->pregnancy_status, ['bred', 'pregnant'])
                    && $b->expected_kidding_date->lt(now()->subDays(7)));

            if ($pregnancy) {
                $days = $pregnancy->expected_kidding_date->startOfDay()->diffInDays(now());
                $alerts[] = [
                    'rule'     => 'pregnancy_overdue',
                    'tag_id'   => $goat->tag_id,
                    'title'    => 'Kidding overdue — '.$goat->tag_id,
                    'detail'   => "{$goat->tag_id} was expected to kid on {$pregnancy->expected_kidding_date->format('M d')} ({$days} days ago) and no kidding has been recorded. Check the doe and record the outcome.",
                    'severity' => 'warning',
                ];
            }
        }

        return $alerts;
    }

    private function vaccinationOverdue(): array
    {
        $stale = now()->subYear()->format('Y-m-d');

        $goats = Goat::inHerd()
            ->whereDoesntHave('healthRecords', fn ($q) => $q
                ->where('type', 'vaccination')
                ->where('date', '>=', $stale))
            ->get();

        return $goats
            ->map(fn (Goat $g) => [
                'rule'     => 'vaccination_overdue',
                'tag_id'   => $g->tag_id,
                'title'    => 'Vaccination overdue — '.$g->tag_id,
                'detail'   => "{$g->tag_id} has no vaccination recorded in the last 12 months. Review the vaccination schedule.",
                'severity' => 'warning',
            ])
            ->all();
    }

    private function weightDrops(): array
    {
        $alerts = [];

        $goats = Goat::inHerd()->has('weightLogs', '>=', 2)->with('weightLogs')->get();

        foreach ($goats as $goat) {
            $logs = $goat->weightLogs->sortByDesc('logged_at')->values();
            $latest = (float) $logs[0]->weight_kg;
            $prev = (float) $logs[1]->weight_kg;

            if ($prev > 0 && (($prev - $latest) / $prev) > 0.10) {
                $pct = round((($prev - $latest) / $prev) * 100, 1);
                $alerts[] = [
                    'rule'     => 'weight_drop',
                    'tag_id'   => $goat->tag_id,
                    'title'    => 'Weight drop — '.$goat->tag_id,
                    'detail'   => "{$goat->tag_id} dropped from {$prev} kg to {$latest} kg (−{$pct}%) between the last two weight entries. Consider a health check.",
                    'severity' => 'alert',
                ];
            }
        }

        return $alerts;
    }
}
