<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\Goat;
use App\Models\Investor;
use App\Models\ProfitDistribution;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // --- Financial KPIs (matching the Google Sheet Dashboard) ---
        $totalCapital      = (float) \DB::table('investments')->sum('amount');
        $currentHerdValue  = (float) Goat::whereNotIn('status', ['sold', 'deceased'])
                                ->selectRaw('COALESCE(SUM(CASE WHEN estimated_value > 0 THEN estimated_value ELSE purchase_price END), 0) as total')
                                ->value('total');
        $realizedProfit    = (float) Goat::where('status', 'sold')
                                ->selectRaw('COALESCE(SUM(sale_price - purchase_price), 0) as total')
                                ->value('total');
        $totalExpenses     = (float) Transaction::expense()->sum('amount');
        $totalIncome       = (float) Transaction::income()->sum('amount');
        $netProfit         = $realizedProfit - $totalExpenses;
        $returnPct         = $totalCapital > 0 ? round($netProfit / $totalCapital * 100, 2) : 0;

        // --- Month KPIs ---
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        $incomeThisMonth = (float) Transaction::income()
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');

        $expenseThisMonth = (float) Transaction::expense()
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');

        // --- Investor positions with settlement ---
        $investors = Investor::with('investments')->orderBy('name')->get();

        // --- Expense breakdown by category ---
        $expenseBreakdown = Transaction::expense()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $expenseByCategory = $expenseBreakdown->map(function ($row) use ($totalExpenses) {
            return (object) [
                'category'   => $row->category,
                'total'      => (float) $row->total,
                'percentage' => $totalExpenses > 0 ? round($row->total / $totalExpenses * 100, 1) : 0,
            ];
        });

        // --- Herd composition by breed ---
        $herdComposition = Breed::select('breeds.name as breed')
            ->selectRaw('COUNT(goats.id) as count')
            ->selectRaw('COALESCE(SUM(CASE WHEN goats.estimated_value > 0 THEN goats.estimated_value ELSE goats.purchase_price END), 0) as value')
            ->leftJoin('goats', function ($join) {
                $join->on('breeds.id', '=', 'goats.breed_id')
                     ->whereNotIn('goats.status', ['sold', 'deceased']);
            })
            ->groupBy('breeds.id', 'breeds.name')
            ->havingRaw('COUNT(goats.id) > 0')
            ->orderByDesc('count')
            ->get()
            ->map(function ($row) use ($currentHerdValue) {
                return (object) [
                    'breed'      => $row->breed,
                    'count'      => (int) $row->count,
                    'value'      => (float) $row->value,
                    'percentage' => $currentHerdValue > 0 ? round($row->value / $currentHerdValue * 100, 1) : 0,
                ];
            });

        $kpis = [
            'total_goats'       => Goat::whereNotIn('status', ['sold', 'deceased'])->count(),
            'total_goats_all'   => Goat::count(),
            'total_investors'   => Investor::count(),
            'total_capital'     => $totalCapital,
            'current_herd_value'=> $currentHerdValue,
            'realized_profit'   => $realizedProfit,
            'total_expenses'    => $totalExpenses,
            'total_income'      => $totalIncome,
            'net_profit'        => $netProfit,
            'return_pct'        => $returnPct,
            'income_this_month' => $incomeThisMonth,
            'expense_this_month'=> $expenseThisMonth,
            'net_this_month'    => $incomeThisMonth - $expenseThisMonth,
            'pending_payouts'   => (float) ProfitDistribution::where('status', 'pending')->sum('amount'),
            'pending_count'     => ProfitDistribution::where('status', 'pending')->count(),
        ];

        $recentTransactions = Transaction::with(['goat', 'investor'])
            ->orderByDesc('date')
            ->limit(8)
            ->get();

        // --- Chart data ---
        $chartData = $this->buildChartData($kpis, $expenseByCategory, $herdComposition);

        return view('dashboard', compact(
            'kpis', 'investors', 'expenseByCategory', 'herdComposition', 'recentTransactions', 'chartData'
        ));
    }

    /**
     * Build all data needed for the Charts tab dashboard.
     */
    private function buildChartData(array $kpis, $expenseByCategory, $herdComposition): array
    {
        // 1. Monthly income vs expense (last 6 months)
        $months = [];
        $incomeByMonth = [];
        $expenseByMonth = [];
        $netProfitByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('M Y');
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $inc = (float) Transaction::income()->whereBetween('date', [$start, $end])->sum('amount');
            $exp = (float) Transaction::expense()->whereBetween('date', [$start, $end])->sum('amount');

            $months[] = $key;
            $incomeByMonth[] = $inc;
            $expenseByMonth[] = $exp;
            $netProfitByMonth[] = $inc - $exp;
        }

        // 2. Expense breakdown by category (reuse existing data)
        $expenseCategories = $expenseByCategory->map(fn ($c) => $c->category)->values()->all();
        $expenseAmounts = $expenseByCategory->map(fn ($c) => round($c->total))->values()->all();

        // 3. Herd composition by breed (reuse existing data)
        $breedNames = $herdComposition->map(fn ($b) => $b->breed)->values()->all();
        $breedCounts = $herdComposition->map(fn ($b) => $b->count)->values()->all();

        // 4. Goat status distribution — "In Herd" = all statuses except sold/deceased
        $goatStatus = [
            'in_herd'  => Goat::whereNotIn('status', ['sold', 'deceased'])->count(),
            'sold'     => Goat::where('status', 'sold')->count(),
            'deceased' => Goat::where('status', 'deceased')->count(),
        ];

        // 5. Investor capital distribution
        $investorNames = Investor::orderBy('name')->pluck('name')->all();
        $investorCapital = Investor::with('investments')
            ->orderBy('name')
            ->get()
            ->map(fn ($inv) => round($inv->total_capital))
            ->values()
            ->all();

        // 6. Breeding/pregnancy status
        $breedingStatus = [
            'bred'     => BreedingRecord::where('pregnancy_status', 'bred')->count(),
            'pregnant' => BreedingRecord::where('pregnancy_status', 'pregnant')->count(),
            'open'     => BreedingRecord::where('pregnancy_status', 'open')->count(),
            'kidded'   => BreedingRecord::where('pregnancy_status', 'kidded')->count(),
        ];

        return [
            'monthly_labels'     => $months,
            'monthly_income'     => $incomeByMonth,
            'monthly_expense'    => $expenseByMonth,
            'monthly_net_profit' => $netProfitByMonth,
            'expense_categories' => $expenseCategories,
            'expense_amounts'    => $expenseAmounts,
            'breed_names'        => $breedNames,
            'breed_counts'       => $breedCounts,
            'goat_status'        => $goatStatus,
            'investor_names'     => $investorNames,
            'investor_capital'   => $investorCapital,
            'breeding_status'    => $breedingStatus,
        ];
    }
}
