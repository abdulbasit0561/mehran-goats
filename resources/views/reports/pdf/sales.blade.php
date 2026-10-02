<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .period { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .num { text-align: right; }
        .summary { margin-bottom: 8px; }
        .summary span { display: inline-block; margin-right: 24px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Mehran Goats — Sales Report</h1>
    <div class="period">Period: {{ $start->format('M d, Y') }} — {{ $end->format('M d, Y') }}</div>

    <div class="summary">
        <span>Revenue: {{ number_format($revenue, 0) }}</span>
        <span>Cash Received: {{ number_format($received, 0) }}</span>
        <span>Outstanding: {{ number_format($outstanding, 0) }}</span>
        <span>Animals Sold: {{ $count }}</span>
        <span>Avg Price: {{ number_format($avgPrice, 0) }}</span>
        <span>Gross Profit: {{ number_format($grossProfit, 0) }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th><th>Goat</th><th>Buyer</th><th>Seller</th>
                <th class="num">Price</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th>
                <th class="num">Purchase</th><th class="num">Profit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                @php $profit = (float) $sale->sale_price - (float) ($sale->goat?->purchase_price ?? 0); @endphp
                <tr>
                    <td>{{ $sale->sale_date->format('M d, Y') }}</td>
                    <td>{{ $sale->goat?->tag_id ?? '—' }}</td>
                    <td>{{ $sale->customer?->name ?? '—' }}</td>
                    <td>{{ $sale->seller?->name ?? '—' }}</td>
                    <td class="num">{{ number_format((float) $sale->sale_price, 0) }}</td>
                    <td class="num">{{ number_format($sale->paid_amount, 0) }}</td>
                    <td class="num">{{ number_format($sale->balance_due, 0) }}</td>
                    <td>{{ ucfirst($sale->status) }}</td>
                    <td class="num">{{ number_format((float) ($sale->goat?->purchase_price ?? 0), 0) }}</td>
                    <td class="num">{{ number_format($profit, 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">No sales in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
