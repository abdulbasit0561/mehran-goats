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
        .period { text-align: center; color: #444; margin-bottom: 20px; font-size: 13px; font-weight: 600; }

        .kpi-grid { display: flex; gap: 10px; margin-bottom: 20px; }
        .kpi-card { flex: 1; border: 1px solid #e0e0e0; border-radius: 6px; padding: 12px; text-align: center; }
        .kpi-card.income { border-left: 4px solid #16a34a; }
        .kpi-card.expense { border-left: 4px solid #ef4444; }
        .kpi-card.net { border-left: 4px solid #1b3a2b; }
        .kpi-card .label { font-size: 9px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; }
        .kpi-card .value { font-size: 16px; font-weight: 700; margin-top: 4px; }
        .kpi-card .value.green { color: #16a34a; }
        .kpi-card .value.red { color: #ef4444; }

        .section-title { font-size: 13px; font-weight: 700; color: #1b3a2b; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; margin: 20px 0 8px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f5f5f5; font-size: 9px; text-transform: uppercase; color: #666; padding: 6px 8px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        td { padding: 5px 8px; border-bottom: 1px solid #f0f0f0; font-size: 11px; }
        td.amount { text-align: right; font-variant-numeric: tabular-nums; }
        td.green { color: #16a34a; font-weight: 600; }
        td.red { color: #ef4444; font-weight: 600; }

        .breakdown-grid { display: flex; gap: 20px; margin-bottom: 20px; }
        .breakdown-col { flex: 1; }
        .breakdown-row { display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid #f5f5f5; font-size: 11px; }

        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #e0e0e0; padding-top: 8px; }
    </style>
</head>
<body>

<div class="header">
    <h1>Mehran Goats</h1>
    <div class="subtitle">Profit &amp; Loss Report</div>
</div>

<div class="period">
    {{ $start->format('F j, Y') }} — {{ $end->format('F j, Y') }}
</div>

{{-- KPI Cards --}}
<div class="kpi-grid">
    <div class="kpi-card income">
        <div class="label">Total Income</div>
        <div class="value green">PKR {{ number_format($totalIncome, 0) }}</div>
    </div>
    <div class="kpi-card expense">
        <div class="label">Total Expense</div>
        <div class="value red">PKR {{ number_format($totalExpense, 0) }}</div>
    </div>
    <div class="kpi-card net">
        <div class="label">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</div>
        <div class="value {{ $netProfit >= 0 ? 'green' : 'red' }}">PKR {{ number_format(abs($netProfit), 0) }}</div>
    </div>
    <div class="kpi-card net">
        <div class="label">Margin</div>
        <div class="value {{ $margin >= 0 ? 'green' : 'red' }}">{{ $margin }}%</div>
    </div>
</div>

{{-- Category Breakdowns --}}
<div class="breakdown-grid">
    <div class="breakdown-col">
        <div class="section-title">Income by Category</div>
        @forelse ($incomeByCat as $cat => $amount)
            <div class="breakdown-row">
                <span>{{ $cat }}</span>
                <span style="color: #16a34a; font-weight: 600;">{{ number_format($amount, 0) }}</span>
            </div>
        @empty
            <div style="color: #999; font-size: 10px; padding: 4px 0;">No income in this period.</div>
        @endforelse
    </div>
    <div class="breakdown-col">
        <div class="section-title">Expense by Category</div>
        @forelse ($expenseByCat as $cat => $amount)
            <div class="breakdown-row">
                <span>{{ $cat }}</span>
                <span style="color: #ef4444; font-weight: 600;">{{ number_format($amount, 0) }}</span>
            </div>
        @empty
            <div style="color: #999; font-size: 10px; padding: 4px 0;">No expenses in this period.</div>
        @endforelse
    </div>
</div>

{{-- Income Detail --}}
<div class="section-title">Income Detail ({{ $income->count() }} transactions)</div>
<table>
    <thead>
        <tr><th>Date</th><th>Category</th><th>Investor</th><th class="amount" style="text-align:right;">Amount</th></tr>
    </thead>
    <tbody>
        @foreach ($income as $t)
            <tr>
                <td>{{ $t->date->format('M d, Y') }}</td>
                <td>{{ $t->category }}</td>
                <td>{{ $t->investor?->name ?? '—' }}</td>
                <td class="amount green">{{ number_format((float) $t->amount, 0) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- Expense Detail --}}
<div class="section-title">Expense Detail ({{ $expense->count() }} transactions)</div>
<table>
    <thead>
        <tr><th>Date</th><th>Category</th><th>Investor</th><th class="amount" style="text-align:right;">Amount</th></tr>
    </thead>
    <tbody>
        @foreach ($expense as $t)
            <tr>
                <td>{{ $t->date->format('M d, Y') }}</td>
                <td>{{ $t->category }}</td>
                <td>{{ $t->investor?->name ?? '—' }}</td>
                <td class="amount red">{{ number_format((float) $t->amount, 0) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    Generated on {{ now()->format('M d, Y \a\t H:i') }} &middot; Mehran Goats Farm Management System
</div>

</body>
</html>
