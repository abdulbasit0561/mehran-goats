<?php

namespace App\Services;

use App\Models\Goat;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Transaction;
use App\Models\User;
use App\Models\YearlyClearance;
use App\Models\YearlyClearancePartner;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes the Yearly Clearance Report for a financial year and creates the
 * permanent completion entry. NEVER modifies existing transactions or
 * investments — everything is snapshot-only.
 */
class YearlyClearanceService
{
    public const PURCHASE_CATEGORY = 'Goat Purchases';
    public const MIN_YEAR = 2020;

    /**
     * Build the full Yearly Clearance Report for a year (reviewable, no writes).
     *
     * @return array{
     *   year: int,
     *   period: array{start: string, end: string},
     *   totals: array{...},
     *   partners: array[],
     *   completed: ?array
     * }
     */
    public function buildReport(int $year): array
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();

        // ---- Farm-wide totals for the year ----
        $totalSales = (float) Transaction::income()
            ->between($start->toDateString(), $end->toDateString())
            ->sum('amount');

        $totalPurchases = (float) Transaction::expense()
            ->between($start->toDateString(), $end->toDateString())
            ->where('category', self::PURCHASE_CATEGORY)
            ->sum('amount');

        $totalExpenses = (float) Transaction::expense()
            ->between($start->toDateString(), $end->toDateString())
            ->sum('amount');

        $businessExpenses = round($totalExpenses - $totalPurchases, 2);
        $grossProfit      = round($totalSales - $totalPurchases, 2);
        $netProfit        = round($totalSales - $totalExpenses, 2);

        // ---- Herd value at year boundaries ----
        $herdValueOpening = $this->herdValueBefore($start);
        $herdValueClosing = $this->herdValueBefore($end->copy()->addDay());

        // ---- Partner lines ----
        $investors = Investor::with(['investments' => fn ($q) => $q->whereDate('date', '<=', $end->toDateString())])->get();
        $totalCapital = (float) $investors->sum(fn ($i) => (float) $i->investments->sum('amount'));

        $previousClearance = YearlyClearance::where('year', $year - 1)->with('partners')->first();

        // Pre-year aggregates for the opening-balance fallback
        $preProfit = $this->netProfitBefore($start);

        $partners = [];
        foreach ($investors as $investor) {
            $capitalAll = (float) $investor->investments->sum('amount');
            $sharePct   = $totalCapital > 0 ? round($capitalAll / $totalCapital * 100, 2) : 0;

            // Investment during THIS year
            $investmentThisYear = (float) $investor->investments
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            // Money the partner received from the farm during the year (income linked to them)
            $withdrawals = (float) Transaction::where('investor_id', $investor->id)
                ->income()->between($start->toDateString(), $end->toDateString())
                ->sum('amount');

            // Expenses the partner paid out of pocket during the year
            $expensesPaid = (float) Transaction::where('investor_id', $investor->id)
                ->expense()->between($start->toDateString(), $end->toDateString())
                ->sum('amount');

            // Opening balance: chain from previous clearance when present
            if ($previousClearance) {
                $opening = (float) optional($previousClearance->partners->firstWhere('investor_id', $investor->id))->final_balance ?? 0;
            } else {
                // Fallback: capital contributed before the year + share of pre-year profit
                $capitalBefore = (float) $investor->investments
                    ->where('date', '<', $start)
                    ->sum('amount');
                $opening = round($capitalBefore + $preProfit * $sharePct / 100, 2);
            }

            $profitShare = round($netProfit * $sharePct / 100, 2);
            $final = round($opening + $investmentThisYear + $profitShare + $expensesPaid - $withdrawals, 2);

            $partners[] = [
                'investor_id'             => $investor->id,
                'investor_name'           => $investor->name,
                'role'                    => $investor->role,
                'share_pct'               => $sharePct,
                'opening_balance'         => $opening,
                'investment'              => round($investmentThisYear, 2),
                'profit_share'            => $profitShare,
                'expenses_paid_by_partner' => round($expensesPaid, 2),
                'withdrawals_received'    => round($withdrawals, 2),
                'final_balance'           => $final,
                'status'                  => $this->statusFor($final),
            ];
        }

        $outstanding = round(collect($partners)->sum('final_balance'), 2);

        return [
            'year'    => $year,
            'period'  => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'totals'  => [
                'total_sales'         => round($totalSales, 2),
                'total_purchases'     => round($totalPurchases, 2),
                'total_expenses'      => round($totalExpenses, 2),
                'total_expenses_business' => $businessExpenses,
                'gross_profit'        => $grossProfit,
                'net_profit'          => $netProfit,
                'herd_value_opening'  => $herdValueOpening,
                'herd_value_closing'  => $herdValueClosing,
                'outstanding_business_balance' => $outstanding,
            ],
            'partners' => $partners,
            'completed' => YearlyClearance::where('year', $year)->first(),
        ];
    }

    /**
     * Create the permanent completion entry for the year. Idempotency-guarded
     * by the unique year constraint + explicit check.
     */
    public function complete(int $year, User $user): YearlyClearance
    {
        return DB::transaction(function () use ($year, $user) {
            if (YearlyClearance::where('year', $year)->exists()) {
                throw new \RuntimeException("Yearly clearance for {$year} already exists.");
            }

            $report = $this->buildReport($year);

            $clearance = YearlyClearance::create([
                'year'            => $year,
                'clearance_date'  => now()->toDateString(),
                'total_sales'     => $report['totals']['total_sales'],
                'total_purchases' => $report['totals']['total_purchases'],
                'total_expenses'  => $report['totals']['total_expenses'],
                'total_expenses_business' => $report['totals']['total_expenses_business'],
                'gross_profit'    => $report['totals']['gross_profit'],
                'net_profit'      => $report['totals']['net_profit'],
                'herd_value_opening'  => $report['totals']['herd_value_opening'],
                'herd_value_closing'  => $report['totals']['herd_value_closing'],
                'outstanding_business_balance' => $report['totals']['outstanding_business_balance'],
                'report_payload'  => [
                    'period'   => $report['period'],
                    'totals'   => $report['totals'],
                    'partners' => $report['partners'],
                ],
                'completed_by' => $user->id,
                'completed_at' => now(),
            ]);

            foreach ($report['partners'] as $line) {
                $clearance->partners()->create([
                    'investor_id'             => $line['investor_id'],
                    'investor_name'           => $line['investor_name'],
                    'share_pct'               => $line['share_pct'],
                    'opening_balance'         => $line['opening_balance'],
                    'investment'              => $line['investment'],
                    'profit_share'            => $line['profit_share'],
                    'expenses_paid_by_partner' => $line['expenses_paid_by_partner'],
                    'withdrawals_received'    => $line['withdrawals_received'],
                    'final_balance'           => $line['final_balance'],
                    'status'                  => $line['status'],
                ]);
            }

            return $clearance;
        });
    }

    private function statusFor(float $final): string
    {
        if (abs($final) < 0.01) {
            return 'settled';
        }

        return $final > 0 ? 'payable_to_partner' : 'receivable_from_partner';
    }

    /** Farm-wide net profit from all records before a date (opening fallback). */
    private function netProfitBefore(Carbon $date): float
    {
        $d = $date->toDateString();
        $income  = (float) Transaction::income()->whereDate('date', '<', $d)->sum('amount');
        $expense = (float) Transaction::expense()->whereDate('date', '<', $d)->sum('amount');

        return round($income - $expense, 2);
    }

    /**
     * Herd value as of a point in time: goats purchased/acquired before or on
     * the date and not yet sold (before sale_date) or died (before death date).
     * Uses estimated_value when set, else purchase_price.
     */
    private function herdValueBefore(Carbon $date): float
    {
        $d = $date->toDateString();

        return round((float) Goat::query()
            // acquired before the boundary (purchase date, or null = origin unknown → counted)
            ->where(function ($q) use ($d) {
                $q->whereNull('purchase_date')->orWhereDate('purchase_date', '<=', $d);
            })
            // not yet sold as of the boundary
            ->where(function ($q) use ($d) {
                $q->whereNull('sale_date')->orWhereDate('sale_date', '>', $d);
            })
            ->whereDoesntHave('deathRecord', fn ($q) => $q->whereDate('death_date', '<=', $d))
            ->selectRaw('COALESCE(SUM(CASE WHEN estimated_value > 0 THEN estimated_value ELSE purchase_price END), 0) as total')
            ->value('total'), 2);
    }
}
