<?php

namespace App\Services;

use App\Models\Investor;
use App\Models\ProfitDistribution;
use App\Models\Transaction;
use Carbon\CarbonInterface;

class ProfitDistributionService
{
    /**
     * Calculate and create profit distribution records for all investors
     * who have a positive capital contribution, for the given date range.
     *
     * @return ProfitDistribution[]  The newly-created distribution records.
     */
    public function distribute(CarbonInterface $periodStart, CarbonInterface $periodEnd): array
    {
        // Idempotency: skip investors who already have a distribution for this exact period
        $existingPeriods = ProfitDistribution::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->pluck('investor_id')
            ->toArray();

        $income  = (float) Transaction::income()->between($periodStart->toDateString(), $periodEnd->toDateString())->sum('amount');
        $expense = (float) Transaction::expense()->between($periodStart->toDateString(), $periodEnd->toDateString())->sum('amount');
        $profit  = $income - $expense;

        $created = [];

        foreach (Investor::all() as $investor) {
            if (in_array($investor->id, $existingPeriods)) {
                continue; // already distributed for this period
            }

            $sharePct = (float) $investor->share_pct;
            if ($sharePct <= 0) {
                continue; // no stake — skip
            }

            $created[] = ProfitDistribution::create([
                'investor_id'  => $investor->id,
                'period_start' => $periodStart->toDateString(),
                'period_end'   => $periodEnd->toDateString(),
                'total_profit' => $profit,
                'share_pct'    => $sharePct,
                'amount'       => round($profit * $sharePct / 100, 2),
                'status'       => 'pending',
            ]);
        }

        return $created;
    }
}
