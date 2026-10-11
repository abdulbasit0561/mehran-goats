<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📋 Budget Planner — {{ $year }}</h2>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <form method="POST" action="{{ route('budget.seed') }}" class="inline" data-confirm="Seed default budget rows for {{ $year }}?">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}" />
                    <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded-md text-sm font-medium hover:bg-amber-600">🌱 Seed Year</button>
                </form>
                <a href="{{ route('budget.create', ['year' => $year]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">+ Add Row</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-md bg-green-50 p-3 sm:p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Year selector --}}
            <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 mb-4 sm:mb-6 flex flex-wrap items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Budget Year:</label>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($availableYears as $y)
                        <a href="{{ route('budget.index', ['year' => $y]) }}"
                           class="px-3 py-1 rounded-lg text-sm font-medium {{ $y == $year ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ $y }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- DESKTOP TABLES (hidden on mobile)                              --}}
            {{-- ============================================================= --}}
            {{-- INCOME SECTION --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 desktop-only-table">
                <div class="p-4 border-b border-gray-100 bg-green-50">
                    <h3 class="text-md font-bold text-green-800">📈 Income</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-3 py-2 text-left sticky left-0 bg-gray-50 z-10 min-w-[140px]">Category</th>
                                @foreach (['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $m)
                                    <th class="px-3 py-2 text-right min-w-[90px]">{{ $m }}</th>
                                @endforeach
                                <th class="px-3 py-2 text-right min-w-[100px] font-bold">Annual</th>
                                <th class="px-3 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($incomeEntries as $entry)
                                <tr class="hover:bg-green-50">
                                    <td class="px-3 py-2 font-medium text-gray-900 sticky left-0 bg-white">{{ $entry->category }}</td>
                                    @php $mIdx = 0; @endphp
                                    @foreach (\App\Models\BudgetEntry::MONTHS as $m)
                                        <td class="px-3 py-2 text-right text-gray-700 cursor-pointer hover:bg-green-100"
                                            x-data="{ editing: false, value: {{ $entry->$m }} }"
                                            @click="editing = true"
                                            @click.away="editing = false">
                                            <span x-show="!editing">{{ number_format($entry->$m, 0) }}</span>
                                            <form x-show="editing" method="POST" action="{{ route('budget.inline', $entry) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="month" value="{{ $m }}" />
                                                <input type="number" name="value" :value="value" step="1" min="0"
                                                       class="w-20 border border-indigo-300 rounded px-1 py-0.5 text-right text-sm"
                                                       x-ref="input"
                                                       @keydown.enter.prevent="$el.form.submit()"
                                                       @keydown.escape="editing = false"
                                                       x-init="$watch('editing', v => { if(v) $nextTick(() => $refs.input.focus()) })" />
                                            </form>
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-2 text-right font-bold text-green-700">{{ number_format($entry->annual_total, 0) }}</td>
                                    <td class="px-3 py-2 text-center space-x-2">
                                        <a href="{{ route('budget.update', $entry) }}" onclick="event.preventDefault(); document.getElementById('edit-{{ $entry->id }}').submit()" class="text-indigo-600 hover:underline text-xs">Edit</a>
                                        <form id="edit-{{ $entry->id }}" method="POST" action="{{ route('budget.update', $entry) }}" class="hidden">
                                            @csrf @method('PATCH')
                                        </form>
                                        <form method="POST" action="{{ route('budget.destroy', $entry) }}" class="inline" data-confirm="Delete {{ $entry->category }}?">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline text-xs">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="15" class="px-3 py-4 text-center text-gray-400">No income rows. <button onclick="document.querySelector('form[action*=seed]').submit()" class="text-indigo-600 hover:underline">Seed year</button> or <a href="{{ route('budget.create', ['year' => $year]) }}" class="text-indigo-600 hover:underline">add manually</a></td></tr>
                            @endforelse
                        </tbody>
                        @if ($incomeEntries->count() > 0)
                            <tfoot>
                                <tr class="bg-green-50 font-bold text-green-800">
                                    <td class="px-3 py-2 sticky left-0 bg-green-50">Total Income</td>
                                    @foreach (\App\Models\BudgetEntry::MONTHS as $m)
                                        <td class="px-3 py-2 text-right">{{ number_format($incomeEntries->sum($m), 0) }}</td>
                                    @endforeach
                                    <td class="px-3 py-2 text-right">{{ number_format($incomeEntries->sum('annual_total'), 0) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- EXPENSE SECTION --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 desktop-only-table">
                <div class="p-4 border-b border-gray-100 bg-red-50">
                    <h3 class="text-md font-bold text-red-800">💸 Expenses</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-3 py-2 text-left sticky left-0 bg-gray-50 z-10 min-w-[140px]">Category</th>
                                @foreach (['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $m)
                                    <th class="px-3 py-2 text-right min-w-[90px]">{{ $m }}</th>
                                @endforeach
                                <th class="px-3 py-2 text-right min-w-[100px] font-bold">Annual</th>
                                <th class="px-3 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($expenseEntries as $entry)
                                <tr class="hover:bg-red-50">
                                    <td class="px-3 py-2 font-medium text-gray-900 sticky left-0 bg-white">{{ $entry->category }}</td>
                                    @foreach (\App\Models\BudgetEntry::MONTHS as $m)
                                        <td class="px-3 py-2 text-right text-gray-700 cursor-pointer hover:bg-red-100"
                                            x-data="{ editing: false, value: {{ $entry->$m }} }"
                                            @click="editing = true"
                                            @click.away="editing = false">
                                            <span x-show="!editing">{{ number_format($entry->$m, 0) }}</span>
                                            <form x-show="editing" method="POST" action="{{ route('budget.inline', $entry) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="month" value="{{ $m }}" />
                                                <input type="number" name="value" :value="value" step="1" min="0"
                                                       class="w-20 border border-indigo-300 rounded px-1 py-0.5 text-right text-sm"
                                                       x-ref="input"
                                                       @keydown.enter.prevent="$el.form.submit()"
                                                       @keydown.escape="editing = false"
                                                       x-init="$watch('editing', v => { if(v) $nextTick(() => $refs.input.focus()) })" />
                                            </form>
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-2 text-right font-bold text-red-700">{{ number_format($entry->annual_total, 0) }}</td>
                                    <td class="px-3 py-2 text-center space-x-2">
                                        <form method="POST" action="{{ route('budget.destroy', $entry) }}" class="inline" data-confirm="Delete {{ $entry->category }}?">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline text-xs">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="15" class="px-3 py-4 text-center text-gray-400">No expense rows. <button onclick="document.querySelector('form[action*=seed]').submit()" class="text-indigo-600 hover:underline">Seed year</button> or <a href="{{ route('budget.create', ['year' => $year]) }}" class="text-indigo-600 hover:underline">add manually</a></td></tr>
                            @endforelse
                        </tbody>
                        @if ($expenseEntries->count() > 0)
                            <tfoot>
                                <tr class="bg-red-50 font-bold text-red-800">
                                    <td class="px-3 py-2 sticky left-0 bg-red-50">Total Expenses</td>
                                    @foreach (\App\Models\BudgetEntry::MONTHS as $m)
                                        <td class="px-3 py-2 text-right">{{ number_format($expenseEntries->sum($m), 0) }}</td>
                                    @endforeach
                                    <td class="px-3 py-2 text-right">{{ number_format($expenseEntries->sum('annual_total'), 0) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE BUDGET VIEW (card-based, no horizontal scroll)          --}}
            {{-- ============================================================= --}}
            <div class="mobile-card-list">
                {{-- INCOME CARDS --}}
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-green-700">📈 Income</h3>
                    <a href="{{ route('budget.create', ['year' => $year]) }}" class="text-xs text-indigo-600 font-medium">+ Add Row</a>
                </div>
                @forelse ($incomeEntries as $entry)
                    @php $monthLabels = ['jan'=>'Jan','feb'=>'Feb','mar'=>'Mar','apr'=>'Apr','may'=>'May','jun'=>'Jun','jul'=>'Jul','aug'=>'Aug','sep'=>'Sep','oct'=>'Oct','nov'=>'Nov','dec'=>'Dec']; @endphp
                    <div class="budget-mobile-card">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-gray-900 text-sm">{{ $entry->category }}</span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-green-700 text-sm">{{ number_format($entry->annual_total, 0) }}</span>
                                <a href="{{ route('budget.update', $entry) }}" onclick="event.preventDefault(); document.getElementById('m-edit-{{ $entry->id }}').submit()" class="text-indigo-600 text-xs">Edit</a>
                                <form id="m-edit-{{ $entry->id }}" method="POST" action="{{ route('budget.update', $entry) }}" class="hidden">
                                    @csrf @method('PATCH')
                                </form>
                                <form method="POST" action="{{ route('budget.destroy', $entry) }}" class="inline" data-confirm="Delete {{ $entry->category }}?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 text-xs">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div class="budget-month-grid">
                            @foreach (\App\Models\BudgetEntry::MONTHS as $m)
                                <div class="budget-month-item"
                                     x-data="{ editing: false, value: {{ $entry->$m }} }"
                                     @click="editing = true"
                                     @click.away="editing = false">
                                    <div class="month-label">{{ $monthLabels[$m] }}</div>
                                    <div class="month-value {{ $entry->$m == 0 ? 'zero' : '' }}">
                                        <span x-show="!editing">{{ number_format($entry->$m, 0) }}</span>
                                        <form x-show="editing" method="POST" action="{{ route('budget.inline', $entry) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="month" value="{{ $m }}" />
                                            <input type="number" name="value" :value="value" step="1" min="0"
                                                   class="w-14 border border-indigo-300 rounded px-1 py-0.5 text-right text-xs"
                                                   x-ref="input"
                                                   @keydown.enter.prevent="$el.form.submit()"
                                                   @keydown.escape="editing = false"
                                                   x-init="$watch('editing', v => { if(v) $nextTick(() => $refs.input.focus()) })" />
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="budget-mobile-card text-center py-6">
                        <p class="text-gray-400 text-sm mb-2">No income rows.</p>
                        <button onclick="document.querySelector('form[action*=seed]').submit()" class="text-indigo-600 text-sm font-medium">Seed Year</button>
                    </div>
                @endforelse

                {{-- EXPENSE CARDS --}}
                <div class="flex items-center justify-between mb-3 mt-6">
                    <h3 class="text-sm font-bold text-red-700">💸 Expenses</h3>
                </div>
                @forelse ($expenseEntries as $entry)
                    @php $monthLabels = ['jan'=>'Jan','feb'=>'Feb','mar'=>'Mar','apr'=>'Apr','may'=>'May','jun'=>'Jun','jul'=>'Jul','aug'=>'Aug','sep'=>'Sep','oct'=>'Oct','nov'=>'Nov','dec'=>'Dec']; @endphp
                    <div class="budget-mobile-card">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-gray-900 text-sm">{{ $entry->category }}</span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-red-700 text-sm">{{ number_format($entry->annual_total, 0) }}</span>
                                <form method="POST" action="{{ route('budget.destroy', $entry) }}" class="inline" data-confirm="Delete {{ $entry->category }}?">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 text-xs">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div class="budget-month-grid">
                            @foreach (\App\Models\BudgetEntry::MONTHS as $m)
                                <div class="budget-month-item"
                                     x-data="{ editing: false, value: {{ $entry->$m }} }"
                                     @click="editing = true"
                                     @click.away="editing = false">
                                    <div class="month-label">{{ $monthLabels[$m] }}</div>
                                    <div class="month-value {{ $entry->$m == 0 ? 'zero' : '' }}">
                                        <span x-show="!editing">{{ number_format($entry->$m, 0) }}</span>
                                        <form x-show="editing" method="POST" action="{{ route('budget.inline', $entry) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="month" value="{{ $m }}" />
                                            <input type="number" name="value" :value="value" step="1" min="0"
                                                   class="w-14 border border-indigo-300 rounded px-1 py-0.5 text-right text-xs"
                                                   x-ref="input"
                                                   @keydown.enter.prevent="$el.form.submit()"
                                                   @keydown.escape="editing = false"
                                                   x-init="$watch('editing', v => { if(v) $nextTick(() => $refs.input.focus()) })" />
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="budget-mobile-card text-center py-6">
                        <p class="text-gray-400 text-sm mb-2">No expense rows.</p>
                        <button onclick="document.querySelector('form[action*=seed]').submit()" class="text-indigo-600 text-sm font-medium">Seed Year</button>
                    </div>
                @endforelse
            </div>

            {{-- ============================================================= --}}
            {{-- NET PROFIT / SURPLUS                                          --}}
            {{-- ============================================================= --}}
            @if ($incomeEntries->count() > 0 || $expenseEntries->count() > 0)
                @php
                    $totalIncome = $incomeEntries->sum('annual_total');
                    $totalExpense = $expenseEntries->sum('annual_total');
                    $net = $totalIncome - $totalExpense;
                @endphp
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg rounded-xl p-4 sm:p-6 mt-4 sm:mt-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900">Annual Summary</h3>
                        <div class="text-left sm:text-right">
                            <p class="text-xs sm:text-sm text-gray-500">Total Income: <span class="font-medium text-green-600">{{ number_format($totalIncome, 0) }}</span></p>
                            <p class="text-xs sm:text-sm text-gray-500">Total Expenses: <span class="font-medium text-red-600">{{ number_format($totalExpense, 0) }}</span></p>
                            <p class="text-base sm:text-lg font-bold {{ $net >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                Net {{ $net >= 0 ? 'Surplus' : 'Deficit' }}: {{ number_format(abs($net), 0) }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
