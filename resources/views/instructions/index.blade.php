<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">ℹ️ Instructions & Guide</h2>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Welcome --}}
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-2">🐐 Welcome to Mehran Goats</h3>
                <p class="text-sm text-gray-600">
                    This app helps you manage your goat farm's investors, livestock, expenses, and budget.
                    Below is a guide to each section. Fields highlighted in
                    <span class="bg-blue-50 text-blue-700 px-1 rounded font-medium">blue</span> are editable inputs;
                    fields in <span class="font-medium text-gray-900">bold black</span> are auto-calculated;
                    <span class="font-bold text-amber-600">gold</span> values are key totals.
                </p>
            </div>

            {{-- Investors --}}
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-3">👥 Investors & Settlement</h3>
                <ul class="space-y-2 text-sm text-gray-600 list-disc list-inside">
                    <li><strong>Capital Contributed</strong> — Each investor's total capital investment. Add investments from the investor detail page.</li>
                    <li><strong>Ownership %</strong> — Auto-calculated as <code>investor_capital / total_capital × 100</code>. Updates automatically as investments change.</li>
                    <li><strong>Role</strong> — Mark an investor as "Manager" if they hold all the farm funds (the person physically paying bills).</li>
                    <li><strong>Expense Split %</strong> — Editable per investor. Must total 100% across all investors. Used to determine how much each person owes.</li>
                    <li><strong>Settlement Balance</strong> — <code>Paid − Owed</code>. Positive = the investor is owed money (overpaid). Negative = they owe the pool/manager.</li>
                </ul>
            </div>

            {{-- Livestock --}}
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-3">🐐 Livestock Ledger</h3>
                <ul class="space-y-2 text-sm text-gray-600 list-disc list-inside">
                    <li><strong>Tag ID</strong> — Unique ear-tag identifier for each goat.</li>
                    <li><strong>Purchase Date / Price / Weight / Vendor</strong> — Record when and from whom each goat was purchased.</li>
                    <li><strong>Estimated Current Value</strong> — Your assessment of the goat's current market value (for in-herd goats).</li>
                    <li><strong>Sale Date / Price / Weight</strong> — Fill in when a goat is sold. The status should be set to "Sold".</li>
                    <li><strong>Realized Gain/Loss</strong> — Auto-calculated as <code>sale_price − purchase_price</code> for sold goats.</li>
                    <li><strong>Days Held</strong> — Auto-calculated from purchase date to sale date (or today if still in herd).</li>
                    <li><strong>Mortality Rate</strong> — <code>deceased_count / total_purchased × 100</code>. Shown in the herd stats bar.</li>
                    <li><strong>Statuses</strong>: Kid → Grower → Breeder → Sold / Deceased.</li>
                </ul>
            </div>

            {{-- Expenses --}}
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-3">💸 Expenses & Income</h3>
                <ul class="space-y-2 text-sm text-gray-600 list-disc list-inside">
                    <li><strong>Type</strong> — Income or Expense. Category options change based on type.</li>
                    <li><strong>Paid By</strong> — Link to the investor who physically paid this expense. Used in settlement calculations.</li>
                    <li><strong>Payment Method</strong> — Cash, Bank Transfer, Check, or Other.</li>
                    <li><strong>Budget Amount</strong> — Optional. If set, the Variance column shows <code>budget − actual</code>.</li>
                    <li><strong>Recurring</strong> — Flag for expenses that repeat regularly (e.g. monthly feed).</li>
                    <li><strong>Categories</strong>: Feed & Fodder, Veterinary & Medicine, Labor/Wages, Shelter & Maintenance, Transport, Breeding Costs, Utilities, Equipment & Tools, Goat Purchases, Other.</li>
                </ul>
            </div>

            {{-- Dashboard --}}
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-3">📊 Dashboard</h3>
                <ul class="space-y-2 text-sm text-gray-600 list-disc list-inside">
                    <li><strong>Total Capital</strong> — Sum of all investor contributions.</li>
                    <li><strong>Current Herd Value</strong> — Sum of estimated values for all goats still in the herd.</li>
                    <li><strong>Realized Profit</strong> — Sum of <code>(sale_price − purchase_price)</code> for all sold goats.</li>
                    <li><strong>Net Profit</strong> — <code>Realized Profit − Total Expenses</code>.</li>
                    <li><strong>Return %</strong> — <code>Net Profit / Total Capital × 100</code>.</li>
                    <li><strong>Investor Positions</strong> — Settlement summary for all investors.</li>
                    <li><strong>Expenses by Category</strong> — Bar chart breakdown with percentages.</li>
                    <li><strong>Herd Composition</strong> — Goat count and value by breed.</li>
                </ul>
            </div>

            {{-- Budget Planner --}}
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-3">📋 Budget Planner</h3>
                <ul class="space-y-2 text-sm text-gray-600 list-disc list-inside">
                    <li><strong>Annual Grid</strong> — Plan income and expenses across 12 months.</li>
                    <li><strong>Inline Editing</strong> — Click any cell to edit the value. Press Enter to save, Escape to cancel.</li>
                    <li><strong>Seed Year</strong> — Auto-creates all standard category rows (both income and expense) for the selected year with zero values.</li>
                    <li><strong>Auto-Totals</strong> — Monthly and annual totals calculate automatically. Net Surplus/Deficit shown at the bottom.</li>
                    <li><strong>Year Selector</strong> — Switch between budget years using the tabs.</li>
                </ul>
            </div>

            {{-- Reports --}}
            <div class="bg-white shadow-sm rounded-lg p-4 sm:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-3">📈 Reports</h3>
                <ul class="space-y-2 text-sm text-gray-600 list-disc list-inside">
                    <li><strong>Profit & Loss</strong> — View income vs expenses for a selected date range.</li>
                    <li><strong>Distribute Profits</strong> — Auto-calculate profit distributions to investors based on ownership %.</li>
                    <li><strong>Investor Statements</strong> — Per-investor financial detail with CSV export.</li>
                </ul>
            </div>

            {{-- Settlement tip --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 sm:p-6">
                <h3 class="text-md font-bold text-blue-900 mb-2">💡 Settlement Tip</h3>
                <p class="text-sm text-blue-800">
                    The Manager physically holds all the funds and pays expenses. When settling:
                    investors with a <strong class="text-green-700">positive balance</strong> are owed money
                    (they paid more than their share), while those with a <strong class="text-red-700">negative balance</strong>
                    need to contribute more to the pool.
                </p>
            </div>

            {{-- Mortality note --}}
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 sm:p-6">
                <h3 class="text-md font-bold text-amber-900 mb-2">⚠️ Mortality Tracking</h3>
                <p class="text-sm text-amber-800">
                    Mark any goat that dies as "Deceased" in the status. The mortality rate is calculated
                    as <code>deaths / total_purchased × 100</code>. A rate above 15% is flagged in red.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
