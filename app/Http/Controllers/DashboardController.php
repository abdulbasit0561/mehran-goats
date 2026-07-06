<?php

namespace App\Http\Controllers;

use App\Models\Breed;
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
        $currentHerdValue  = (float) Goat::whereNotIn('status', ['sold', 'deceased'])->sum('estimated_value');
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
            ->selectRaw('COALESCE(SUM(goats.estimated_value), 0) as value')
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

        return view('dashboard', compact(
            'kpis', 'investors', 'expenseByCategory', 'herdComposition', 'recentTransactions'
        ));
    }
}
