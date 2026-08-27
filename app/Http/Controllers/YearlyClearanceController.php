<?php

namespace App\Http\Controllers;

use App\Models\YearlyClearance;
use App\Services\YearlyClearanceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class YearlyClearanceController extends Controller
{
    public function __construct(
        private YearlyClearanceService $service,
    ) {
    }

    /**
     * Completed clearances list + year-over-year comparison.
     */
    public function index()
    {
        $clearances = YearlyClearance::with(['partners', 'completer'])
            ->orderByDesc('year')
            ->get();

        return view('reports.yearly-clearance.index', compact('clearances'));
    }

    /**
     * Reviewable Yearly Clearance Report for a year (before/after completion).
     */
    public function report(Request $request)
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $report = $this->service->buildReport($year);

        $completedYears = YearlyClearance::orderByDesc('year')->pluck('year');

        return view('reports.yearly-clearance.report', compact('report', 'year', 'completedYears'));
    }

    /**
     * Complete the yearly clearance — creates the permanent entry.
     */
    public function complete(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
        ], [
            'year.max' => 'Financial year cannot be more than one year in the future.',
            'year.min' => 'Financial year must be 2020 or later.',
        ]);

        $year = (int) $validated['year'];

        if (YearlyClearance::where('year', $year)->exists()) {
            return redirect()->route('reports.yearly-clearance.report', ['year' => $year])
                ->with('error', "Yearly clearance for {$year} has already been completed.");
        }

        $clearance = $this->service->complete($year, $request->user());

        return redirect()->route('reports.yearly-clearance.show', $clearance)
            ->with('success', "Yearly clearance for FY {$year} completed. The completion entry is now a permanent historical record.");
    }

    /**
     * View a completed clearance entry.
     */
    public function show(YearlyClearance $clearance)
    {
        $clearance->load(['partners.investor', 'completer']);

        return view('reports.yearly-clearance.show', compact('clearance'));
    }

    /**
     * CSV export of a completed clearance.
     */
    public function csv(YearlyClearance $clearance): StreamedResponse
    {
        $clearance->load('partners');

        $filename = "yearly-clearance-fy{$clearance->year}.csv";
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($clearance) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Mehran Goats — Yearly Clearance Report']);
            fputcsv($handle, ["Financial Year: FY {$clearance->year} ({$clearance->report_payload['period']['start']} to {$clearance->report_payload['period']['end']})"]);
            fputcsv($handle, ["Clearance Date: {$clearance->clearance_date->format('M d, Y')}"]);
            fputcsv($handle, ["Completed By: " . ($clearance->completer?->name ?? 'Unknown')]);
            fputcsv($handle, ["Completed At: " . $clearance->completed_at?->format('M d, Y H:i')]);
            fputcsv($handle, []);

            fputcsv($handle, ['FINANCIAL SUMMARY']);
            fputcsv($handle, ['Total Sales / Revenue', number_format((float) $clearance->total_sales, 2)]);
            fputcsv($handle, ['Total Purchases', number_format((float) $clearance->total_purchases, 2)]);
            fputcsv($handle, ['Total Expenses (incl. purchases)', number_format((float) $clearance->total_expenses, 2)]);
            fputcsv($handle, ['Business Expenses (excl. purchases)', number_format((float) $clearance->total_expenses_business, 2)]);
            fputcsv($handle, ['Gross Profit', number_format((float) $clearance->gross_profit, 2)]);
            fputcsv($handle, [$clearance->net_profit >= 0 ? 'Net Profit' : 'Net Loss', number_format(abs((float) $clearance->net_profit), 2)]);
            fputcsv($handle, ['Herd Value (Opening)', number_format((float) $clearance->herd_value_opening, 2)]);
            fputcsv($handle, ['Herd Value (Closing)', number_format((float) $clearance->herd_value_closing, 2)]);
            fputcsv($handle, ['Outstanding Business Balance', number_format((float) $clearance->outstanding_business_balance, 2)]);
            fputcsv($handle, []);

            fputcsv($handle, ['PARTNER-WISE SETTLEMENT']);
            fputcsv($handle, [
                'Partner', 'Share %', 'Opening Balance', 'Investment', 'Profit/Loss Share',
                'Expenses Paid by Partner', 'Withdrawals Received', 'Final Balance', 'Status',
            ]);
            foreach ($clearance->partners as $p) {
                fputcsv($handle, [
                    $p->investor_name,
                    number_format((float) $p->share_pct, 2) . '%',
                    number_format((float) $p->opening_balance, 2),
                    number_format((float) $p->investment, 2),
                    number_format((float) $p->profit_share, 2),
                    number_format((float) $p->expenses_paid_by_partner, 2),
                    number_format((float) $p->withdrawals_received, 2),
                    number_format((float) $p->final_balance, 2),
                    $p->status_label,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * PDF export of a completed clearance.
     */
    public function pdf(YearlyClearance $clearance)
    {
        $clearance->load(['partners', 'completer']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf.yearly-clearance', compact('clearance'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("yearly-clearance-fy{$clearance->year}.pdf");
    }
}
