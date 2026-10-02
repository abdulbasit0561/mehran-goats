<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Models\ProfitDistribution;
use App\Models\Transaction;
use App\Services\ProfitDistributionService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Profit & Loss report — graphs + data, with filters and export.
     */
    public function profitLoss(Request $request)
    {
        // Default range: full current year (not just current month) so the report
        // shows real activity immediately instead of zeros in a quiet month.
        $start = $request->get('start') ? Carbon::parse($request->get('start'))->startOfDay() : Carbon::now()->startOfYear();
        $end   = $request->get('end')   ? Carbon::parse($request->get('end'))->endOfDay()   : Carbon::now()->endOfYear();

        $category = $request->get('category', '');
        $type     = $request->get('type', '');

        // Build filtered queries
        $incomeQuery  = Transaction::income()->between($start->toDateString(), $end->toDateString());
        $expenseQuery = Transaction::expense()->between($start->toDateString(), $end->toDateString());

        if ($category) {
            $incomeQuery->where('category', $category);
            $expenseQuery->where('category', $category);
        }

        $income  = $incomeQuery->with(['investor', 'goat'])->orderByDesc('date')->get();
        $expense = $expenseQuery->with(['investor', 'goat'])->orderByDesc('date')->get();

        $totalIncome  = (float) $income->sum('amount');
        $totalExpense = (float) $expense->sum('amount');
        $netProfit    = $totalIncome - $totalExpense;
        $margin       = $totalIncome > 0 ? round(($netProfit / $totalIncome) * 100, 1) : 0;

        // All transactions for display (filtered by type selection)
        if ($type === 'income') {
            $allTransactions = $income;
        } elseif ($type === 'expense') {
            $allTransactions = $expense;
        } else {
            $allTransactions = $income->concat($expense)->sortByDesc('date')->values();
        }

        // Distributions scoped to selected period
        $distributions = ProfitDistribution::with('investor')
            ->where('period_start', '>=', $start->toDateString())
            ->where('period_end', '<=', $end->toDateString())
            ->latest()
            ->get();

        // Chart data
        $chartData = $this->buildReportChartData($start, $end, $income, $expense);

        // Category lists for filter dropdowns
        $incomeCategories  = Transaction::INCOME_CATEGORIES;
        $expenseCategories = Transaction::EXPENSE_CATEGORIES;

        return view('reports.profit-loss', compact(
            'start', 'end', 'category', 'type',
            'income', 'expense', 'allTransactions',
            'totalIncome', 'totalExpense', 'netProfit', 'margin',
            'distributions', 'chartData',
            'incomeCategories', 'expenseCategories',
        ));
    }

    /**
     * Build chart data for the Graphs tab.
     */
    private function buildReportChartData(Carbon $start, Carbon $end, $income, $expense): array
    {
        // 1. Monthly income vs expense within range
        $monthlyLabels = [];
        $monthlyIncome = [];
        $monthlyExpense = [];
        $monthlyNet = [];

        $period = CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->startOfMonth());
        foreach ($period as $month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd   = $month->copy()->endOfMonth();

            $inc = (float) $income->filter(fn ($t) => $t->date >= $monthStart && $t->date <= $monthEnd)->sum('amount');
            $exp = (float) $expense->filter(fn ($t) => $t->date >= $monthStart && $t->date <= $monthEnd)->sum('amount');

            $monthlyLabels[] = $month->format('M Y');
            $monthlyIncome[] = $inc;
            $monthlyExpense[] = $exp;
            $monthlyNet[] = $inc - $exp;
        }

        // 2. Expense breakdown by category
        $expenseByCat = $expense->groupBy('category')->map(fn ($items) => (float) $items->sum('amount'))->sortDesc();
        // 3. Income breakdown by category
        $incomeByCat = $income->groupBy('category')->map(fn ($items) => (float) $items->sum('amount'))->sortDesc();

        // 4. Daily cashflow within range
        $dailyLabels = [];
        $dailyNet = [];
        $dayPeriod = CarbonPeriod::create($start, $end);
        $dayChunks = [];
        $currentChunk = [];
        $dayCount = 0;
        foreach ($dayPeriod as $day) {
            $inc = (float) $income->filter(fn ($t) => $t->date->isSameDay($day))->sum('amount');
            $exp = (float) $expense->filter(fn ($t) => $t->date->isSameDay($day))->sum('amount');
            $net = $inc - $exp;
            if ($net != 0) {
                $dailyLabels[] = $day->format('M d');
                $dailyNet[] = $net;
            }
        }
        // Limit to last 30 data points for readability
        if (count($dailyLabels) > 30) {
            $dailyLabels = array_slice($dailyLabels, -30);
            $dailyNet = array_slice($dailyNet, -30);
        }

        return [
            'monthly_labels'  => $monthlyLabels,
            'monthly_income'  => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'monthly_net'     => $monthlyNet,
            'expense_cat_labels'  => $expenseByCat->keys()->all(),
            'expense_cat_values'  => $expenseByCat->values()->all(),
            'income_cat_labels'   => $incomeByCat->keys()->all(),
            'income_cat_values'   => $incomeByCat->values()->all(),
            'daily_labels'    => $dailyLabels,
            'daily_net'       => $dailyNet,
        ];
    }

    /**
     * Distribute profit for the period.
     */
    public function distribute(Request $request)
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end'   => ['required', 'date', 'after_or_equal:start'],
        ]);

        $start = Carbon::parse($validated['start'])->startOfDay();
        $end   = Carbon::parse($validated['end'])->endOfDay();

        // Guard: don't distribute losses
        $profit = (float) Transaction::income()->between($start->toDateString(), $end->toDateString())->sum('amount')
                - (float) Transaction::expense()->between($start->toDateString(), $end->toDateString())->sum('amount');

        if ($profit <= 0) {
            return redirect()->route('reports.profit-loss', ['start' => $start->toDateString(), 'end' => $end->toDateString()])
                ->with('error', 'No profit to distribute for this period (net is ' . number_format($profit, 2) . ').');
        }

        $created = app(ProfitDistributionService::class)->distribute($start, $end);

        $msg = count($created)
            ? count($created) . ' distribution record(s) created.'
            : 'No new distributions — already distributed for this period or no investors with capital.';

        return redirect()->route('reports.profit-loss', ['start' => $start->toDateString(), 'end' => $end->toDateString()])
            ->with('success', $msg);
    }

    /**
     * Mark a distribution as paid.
     */
    public function markPaid(ProfitDistribution $distribution)
    {
        $distribution->update(['status' => 'paid']);

        return redirect()->back()->with('success', 'Distribution marked as paid.');
    }

    /**
     * Export P&L as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        // Keep the export's default range in sync with profitLoss().
        $start = $request->get('start') ? Carbon::parse($request->get('start'))->startOfDay() : Carbon::now()->startOfYear();
        $end   = $request->get('end')   ? Carbon::parse($request->get('end'))->endOfDay()   : Carbon::now()->endOfYear();
        $category = $request->get('category', '');
        $type     = $request->get('type', '');

        $query = Transaction::with(['investor', 'goat'])
            ->between($start->toDateString(), $end->toDateString())
            ->orderByDesc('date');

        if ($category) {
            $query->where('category', $category);
        }
        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->get();

        $totalIncome  = (float) $transactions->where('type', 'income')->sum('amount');
        $totalExpense = (float) $transactions->where('type', 'expense')->sum('amount');
        $netProfit    = $totalIncome - $totalExpense;

        $filename = "profit-loss-{$start->format('Y-m-d')}-to-{$end->format('Y-m-d')}.csv";

        return response()->stream(function () use ($transactions, $start, $end, $totalIncome, $totalExpense, $netProfit) {
            $handle = fopen('php://output', 'w');

            // Title
            fputcsv($handle, ['Mehran Goats — Profit & Loss Report']);
            fputcsv($handle, ["Period: {$start->format('M d, Y')} — {$end->format('M d, Y')}"]);
            fputcsv($handle, []);
            fputcsv($handle, ['SUMMARY']);
            fputcsv($handle, ['Total Income', number_format($totalIncome, 2)]);
            fputcsv($handle, ['Total Expense', number_format($totalExpense, 2)]);
            fputcsv($handle, ['Net ' . ($netProfit >= 0 ? 'Profit' : 'Loss'), number_format(abs($netProfit), 2)]);
            fputcsv($handle, []);

            // Transactions
            fputcsv($handle, ['TRANSACTIONS']);
            fputcsv($handle, ['Date', 'Type', 'Category', 'Amount', 'Investor', 'Notes']);
            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->date->format('Y-m-d'),
                    ucfirst($t->type),
                    $t->category,
                    number_format((float) $t->amount, 2),
                    $t->investor?->name ?? '',
                    $t->notes ?? '',
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export P&L as PDF.
     */
    public function exportPdf(Request $request)
    {
        // Keep the export's default range in sync with profitLoss().
        $start = $request->get('start') ? Carbon::parse($request->get('start'))->startOfDay() : Carbon::now()->startOfYear();
        $end   = $request->get('end')   ? Carbon::parse($request->get('end'))->endOfDay()   : Carbon::now()->endOfYear();
        $category = $request->get('category', '');
        $type     = $request->get('type', '');

        $incomeQuery  = Transaction::income()->between($start->toDateString(), $end->toDateString())->with(['investor']);
        $expenseQuery = Transaction::expense()->between($start->toDateString(), $end->toDateString())->with(['investor']);

        if ($category) {
            $incomeQuery->where('category', $category);
            $expenseQuery->where('category', $category);
        }

        $income  = $incomeQuery->orderByDesc('date')->get();
        $expense = $expenseQuery->orderByDesc('date')->get();

        $totalIncome  = (float) $income->sum('amount');
        $totalExpense = (float) $expense->sum('amount');
        $netProfit    = $totalIncome - $totalExpense;
        $margin       = $totalIncome > 0 ? round(($netProfit / $totalIncome) * 100, 1) : 0;

        $expenseByCat = $expense->groupBy('category')->map(fn ($items) => (float) $items->sum('amount'))->sortDesc();
        $incomeByCat  = $income->groupBy('category')->map(fn ($items) => (float) $items->sum('amount'))->sortDesc();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf.profit-loss', compact(
            'start', 'end', 'income', 'expense',
            'totalIncome', 'totalExpense', 'netProfit', 'margin',
            'expenseByCat', 'incomeByCat',
        ))->setPaper('a4', 'portrait');

        $filename = "profit-loss-{$start->format('Y-m-d')}-to-{$end->format('Y-m-d')}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Investor list report.
     */
    public function investorIndex()
    {
        $investors = Investor::with('investments')->latest()->get();

        return view('reports.investors', compact('investors'));
    }

    /**
     * Single investor statement.
     */
    public function investorStatement(Investor $investor)
    {
        $investor->load(['investments' => fn ($q) => $q->latest('date'),
                         'profitDistributions']);

        $transactions = $investor->transactions()->latest('date')->paginate(20);

        return view('reports.investor-statement', compact('investor', 'transactions'));
    }

    /**
     * Investor statement CSV.
     */
    public function investorCsv(Investor $investor): StreamedResponse
    {
        $investor->load(['investments', 'profitDistributions']);

        $filename = "investor-{$investor->id}-statement.csv";
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($investor) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Investor Statement — ' . $investor->name]);
            fputcsv($handle, []);
            fputcsv($handle, ['Share %', number_format($investor->share_pct, 2) . '%']);
            fputcsv($handle, ['Total Capital Invested', number_format((float) $investor->total_capital, 2)]);
            fputcsv($handle, []);
            fputcsv($handle, ['Investments']);
            fputcsv($handle, ['Date', 'Amount', 'Notes']);
            foreach ($investor->investments as $inv) {
                fputcsv($handle, [$inv->date->format('Y-m-d'), $inv->amount, $inv->notes ?? '']);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Profit Distributions']);
            fputcsv($handle, ['Period Start', 'Period End', 'Share %', 'Total Profit', 'Amount', 'Status']);
            foreach ($investor->profitDistributions as $dist) {
                fputcsv($handle, [
                    $dist->period_start->format('Y-m-d'),
                    $dist->period_end->format('Y-m-d'),
                    number_format($dist->share_pct, 2) . '%',
                    number_format($dist->total_profit, 2),
                    number_format($dist->amount, 2),
                    $dist->status,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
