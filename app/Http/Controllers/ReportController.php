<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Models\ProfitDistribution;
use App\Models\Transaction;
use App\Services\ProfitDistributionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        $start = $request->get('start') ? Carbon::parse($request->get('start'))->startOfDay() : Carbon::now()->startOfMonth();
        $end   = $request->get('end')   ? Carbon::parse($request->get('end'))->endOfDay() : Carbon::now()->endOfMonth();

        $income  = Transaction::income()->between($start->toDateString(), $end->toDateString())->orderByDesc('date')->get();
        $expense = Transaction::expense()->between($start->toDateString(), $end->toDateString())->orderByDesc('date')->get();

        $totalIncome   = (float) $income->sum('amount');
        $totalExpense  = (float) $expense->sum('amount');
        $netProfit     = $totalIncome - $totalExpense;

        // Recent distributions
        $distributions = ProfitDistribution::with('investor')
            ->latest()
            ->limit(20)
            ->get();

        return view('reports.profit-loss', compact('start', 'end', 'income', 'expense', 'totalIncome', 'totalExpense', 'netProfit', 'distributions'));
    }

    public function distribute(Request $request)
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end'   => ['required', 'date', 'after_or_equal:start'],
        ]);

        $created = app(ProfitDistributionService::class)->distribute(
            Carbon::parse($validated['start'])->startOfDay(),
            Carbon::parse($validated['end'])->endOfDay(),
        );

        $msg = count($created)
            ? count($created) . ' distribution record(s) created.'
            : 'No investors with capital found.';

        return redirect()->route('reports.profit-loss')->with('success', $msg);
    }

    public function markPaid(ProfitDistribution $distribution)
    {
        $distribution->update(['status' => 'paid']);

        return redirect()->route('reports.profit-loss')->with('success', 'Distribution marked as paid.');
    }

    public function investorIndex()
    {
        $investors = Investor::with('investments')->latest()->get();

        return view('reports.investors', compact('investors'));
    }

    public function investorStatement(Investor $investor)
    {
        $investor->load(['investments' => fn ($q) => $q->latest('date'),
                         'profitDistributions']);

        // Investor-linked transactions
        $transactions = $investor->transactions()->latest('date')->paginate(20);

        return view('reports.investor-statement', compact('investor', 'transactions'));
    }

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
