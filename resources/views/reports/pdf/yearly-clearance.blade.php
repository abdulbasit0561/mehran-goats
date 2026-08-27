<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1a1a1a; font-size: 12px; line-height: 1.5; }
        .header { text-align: center; border-bottom: 3px solid #1b3a2b; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { color: #1b3a2b; font-size: 22px; margin-bottom: 4px; }
        .header .subtitle { color: #666; font-size: 11px; }
        .period { text-align: center; color: #444; margin-bottom: 6px; font-size: 13px; font-weight: 600; }
        .meta { text-align: center; color: #777; font-size: 10px; margin-bottom: 20px; }

        .kpi-grid { display: flex; gap: 10px; margin-bottom: 20px; }
        .kpi-card { flex: 1; border: 1px solid #e0e0e0; border-radius: 6px; padding: 12px; text-align: center; }
        .kpi-card.sales { border-left: 4px solid #16a34a; }
        .kpi-card.purchases { border-left: 4px solid #f97316; }
        .kpi-card.expense { border-left: 4px solid #ef4444; }
        .kpi-card.net { border-left: 4px solid #1b3a2b; }
        .kpi-card .label { font-size: 9px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; }
        .kpi-card .value { font-size: 15px; font-weight: 700; margin-top: 4px; }
        .kpi-card .value.green { color: #16a34a; }
        .kpi-card .value.red { color: #ef4444; }
        .kpi-card .value.orange { color: #f97316; }

        .section-title { font-size: 13px; font-weight: 700; color: #1b3a2b; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; margin: 20px 0 8px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f5f5f5; font-size: 9px; text-transform: uppercase; color: #666; padding: 6px 8px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        td { padding: 5px 8px; border-bottom: 1px solid #f0f0f0; font-size: 11px; }
        td.amount { text-align: right; font-variant-numeric: tabular-nums; }
        td.green { color: #16a34a; font-weight: 600; }
        td.red { color: #ef4444; font-weight: 600; }
        tfoot td { font-weight: 700; background: #fafafa; }

        .summary-row { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #f5f5f5; font-size: 11px; }
        .summary-row.total { font-weight: 700; background: #f5f5f5; padding: 6px 8px; border: none; margin-top: 4px; }

        .status { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 9px; font-weight: 700; }
        .status.payable { background: #fef3c7; color: #92400e; }
        .status.receivable { background: #fee2e2; color: #991b1b; }
        .status.settled { background: #dcfce7; color: #166534; }

        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #e0e0e0; padding-top: 8px; }
    </style>
</head>
<body>

<div class="header">
    <h1>Mehran Goats</h1>
    <div class="subtitle">Yearly Clearance Report — Annual Settlement</div>
</div>

<div class="period">
    Financial Year {{ $clearance->year }} ({{ \Carbon\Carbon::parse($clearance->report_payload['period']['start'] ?? "{$clearance->year}-01-01")->format('M d, Y') }} — {{ \Carbon\Carbon::parse($clearance->report_payload['period']['end'] ?? "{$clearance->year}-12-31")->format('M d, Y') }})
</div>
<div class="meta">
    Clearance Date: {{ $clearance->clearance_date->format('M d, Y') }} &nbsp;·&nbsp;
    Completed by {{ $clearance->completer?->name ?? 'Unknown' }} on {{ $clearance->completed_at?->format('M d, Y H:i') }}
</div>

{{-- KPI Cards --}}
<div class="kpi-grid">
    <div class="kpi-card sales">
        <div class="label">Total Sales</div>
        <div class="value green">PKR {{ number_format((float) $clearance->total_sales, 0) }}</div>
    </div>
    <div class="kpi-card purchases">
        <div class="label">Total Purchases</div>
        <div class="value orange">PKR {{ number_format((float) $clearance->total_purchases, 0) }}</div>
    </div>
    <div class="kpi-card expense">
        <div class="label">Total Expenses</div>
        <div class="value red">PKR {{ number_format((float) $clearance->total_expenses, 0) }}</div>
    </div>
    <div class="kpi-card net">
        <div class="label">Net {{ $clearance->net_profit >= 0 ? 'Profit' : 'Loss' }}</div>
        <div class="value {{ $clearance->net_profit >= 0 ? 'green' : 'red' }}">PKR {{ number_format(abs((float) $clearance->net_profit), 0) }}</div>
    </div>
</div>

{{-- Financial summary --}}
<div class="section-title">Financial Summary</div>
<div class="summary-row"><span>Total Sales / Revenue (all income)</span><span>PKR {{ number_format((float) $clearance->total_sales, 2) }}</span></div>
<div class="summary-row"><span>Total Purchases (Goat Purchases)</span><span>PKR {{ number_format((float) $clearance->total_purchases, 2) }}</span></div>
<div class="summary-row"><span>Gross Profit (Sales − Purchases)</span><span>PKR {{ number_format((float) $clearance->gross_profit, 2) }}</span></div>
<div class="summary-row"><span>Business Expenses (excl. purchases)</span><span>PKR {{ number_format((float) $clearance->total_expenses_business, 2) }}</span></div>
<div class="summary-row total"><span>Net {{ $clearance->net_profit >= 0 ? 'Profit' : 'Loss' }}</span><span>PKR {{ number_format(abs((float) $clearance->net_profit), 2) }}</span></div>
<div class="summary-row"><span>Herd Value — Opening (Jan 1)</span><span>PKR {{ number_format((float) $clearance->herd_value_opening, 2) }}</span></div>
<div class="summary-row"><span>Herd Value — Closing (Dec 31)</span><span>PKR {{ number_format((float) $clearance->herd_value_closing, 2) }}</span></div>
<div class="summary-row"><span>Outstanding Business Balance</span><span>PKR {{ number_format((float) $clearance->outstanding_business_balance, 2) }}</span></div>

{{-- Partner settlement --}}
<div class="section-title">Partner-wise Settlement</div>
<table>
    <thead>
        <tr>
            <th>Partner</th>
            <th class="amount">Share %</th>
            <th class="amount">Opening</th>
            <th class="amount">Investment</th>
            <th class="amount">Profit/Loss</th>
            <th class="amount">Exp. Paid</th>
            <th class="amount">Withdrawals</th>
            <th class="amount">Final Balance</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($clearance->partners as $p)
            <tr>
                <td>{{ $p->investor_name }}</td>
                <td class="amount">{{ number_format((float) $p->share_pct, 2) }}%</td>
                <td class="amount">{{ number_format((float) $p->opening_balance, 2) }}</td>
                <td class="amount">{{ number_format((float) $p->investment, 2) }}</td>
                <td class="amount {{ $p->profit_share >= 0 ? 'green' : 'red' }}">{{ number_format((float) $p->profit_share, 2) }}</td>
                <td class="amount">{{ number_format((float) $p->expenses_paid_by_partner, 2) }}</td>
                <td class="amount">{{ number_format((float) $p->withdrawals_received, 2) }}</td>
                <td class="amount {{ $p->final_balance >= 0 ? 'green' : 'red' }}">{{ number_format((float) $p->final_balance, 2) }}</td>
                <td>
                    @if ($p->status === 'payable_to_partner')
                        <span class="status payable">Payable to Partner</span>
                    @elseif ($p->status === 'receivable_from_partner')
                        <span class="status receivable">Receivable from Partner</span>
                    @else
                        <span class="status settled">Settled</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align:center; color:#999;">No partners</td></tr>
        @endforelse
    </tbody>
    @if ($clearance->partners->isNotEmpty())
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="amount">{{ number_format((float) $clearance->partners->sum('share_pct'), 2) }}%</td>
                <td class="amount">{{ number_format((float) $clearance->partners->sum('opening_balance'), 2) }}</td>
                <td class="amount">{{ number_format((float) $clearance->partners->sum('investment'), 2) }}</td>
                <td class="amount">{{ number_format((float) $clearance->partners->sum('profit_share'), 2) }}</td>
                <td class="amount">{{ number_format((float) $clearance->partners->sum('expenses_paid_by_partner'), 2) }}</td>
                <td class="amount">{{ number_format((float) $clearance->partners->sum('withdrawals_received'), 2) }}</td>
                <td class="amount">{{ number_format((float) $clearance->outstanding_business_balance, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    @endif
</table>

<div class="footer">
    Permanent historical record — completed {{ $clearance->completed_at?->format('M d, Y H:i') }} by {{ $clearance->completer?->name ?? 'Unknown' }} ·
    Source transactions are never modified by yearly clearance.
</div>

</body>
</html>
