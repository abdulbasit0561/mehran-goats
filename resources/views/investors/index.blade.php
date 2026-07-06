<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">👥 Investors & Settlement</h2>
            <a href="{{ route('investors.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">+ Add Investor</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 page-content">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 page-content-inner">

            @if (session('success'))
                <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-md bg-green-50 p-3 sm:p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- ============================================================= --}}
            {{-- MOBILE: SUMMARY CARDS (Total Capital + Investor Count)        --}}
            {{-- ============================================================= --}}
            <div class="mobile-only grid grid-cols-2 gap-2 mb-3">
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Total Capital</p>
                    <p class="mg-stat-value">{{ number_format($totalCapital, 0) }}</p>
                </div>
                <div class="mg-stat-card-dark">
                    <p class="mg-stat-label">Investors</p>
                    <p class="mg-stat-value">{{ count($investors) }}</p>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- MOBILE: INVESTOR CARDS                                        --}}
            {{-- ============================================================= --}}
            <div class="mobile-only">
                {{-- Expense split warning --}}
                @if (count($investors) > 0 && abs($totalExpenseSplit - 100) > 0.5)
                    <div class="mb-3 rounded-xl bg-amber-50 p-3 border border-amber-200">
                        <p class="text-xs font-medium text-amber-800">
                            ⚠️ Expense Split totals {{ number_format($totalExpenseSplit, 1) }}% — should be 100%.
                        </p>
                    </div>
                @endif

                {{-- Mobile search --}}
                <div class="mg-search mb-3">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <form method="GET" action="{{ route('investors.index') }}">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name / email / phone…" />
                    </form>
                </div>

                @forelse ($investors as $investor)
                    <div class="mobile-card-item">
                        <a href="{{ route('investors.show', $investor) }}" class="block">
                            <div class="mobile-card-row mb-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="mg-avatar">{{ strtoupper(substr($investor->name, 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-semibold text-gray-900 text-sm truncate">{{ $investor->name }}</span>
                                            @if ($investor->isManager())
                                                <span class="mg-pill mg-pill-gray">MGR</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-400 truncate">{{ $investor->email ?? $investor->phone ?? 'Investor' }}</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold text-gray-900">{{ number_format($investor->total_capital, 0) }}</p>
                                    <p class="text-xs text-gray-400">{{ number_format($investor->share_pct, 1) }}%</p>
                                </div>
                            </div>

                            {{-- Progress bar --}}
                            <div class="mg-progress mb-2">
                                <div class="mg-progress-bar" style="width: {{ min($investor->share_pct, 100) }}%"></div>
                            </div>

                            {{-- Payment info + settle --}}
                            <div class="mobile-card-row">
                                <p class="text-xs text-gray-400">Exp paid {{ number_format($investor->expense_paid, 0) }} / owed {{ number_format($investor->expense_owed, 0) }}</p>
                                <span class="mg-settle {{ $investor->settlement_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    Settle {{ $investor->settlement_balance >= 0 ? '+' : '' }}{{ number_format($investor->settlement_balance, 0) }}
                                </span>
                            </div>
                        </a>
                        <div class="flex items-center gap-4 pt-2 mt-2 border-t border-gray-50">
                            <a href="{{ route('investors.edit', $investor) }}" class="text-xs text-indigo-600 font-medium">✏️ Edit</a>
                            <form method="POST" action="{{ route('investors.destroy', $investor) }}" class="inline" onsubmit="return confirm('Delete this investor?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-600 font-medium">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="mobile-card-item text-center py-8">
                        <p class="text-gray-400 text-sm mb-3">No investors yet.</p>
                        <a href="{{ route('investors.create') }}" class="inline-block px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">+ Add Investor</a>
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $investors->withQueryString()->links() }}
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- DESKTOP ONLY (filters + table)                                --}}
            {{-- ============================================================= --}}
            <div class="desktop-only-table">
                {{-- Expense split warning --}}
                @if (count($investors) > 0 && abs($totalExpenseSplit - 100) > 0.5)
                    <div class="mb-4 sm:mb-6 rounded-xl sm:rounded-md bg-amber-50 p-3 sm:p-4 border border-amber-200">
                        <p class="text-xs sm:text-sm font-medium text-amber-800">
                            ⚠️ Expense Split % totals {{ number_format($totalExpenseSplit, 1) }}% — should be 100%.
                        </p>
                    </div>
                @endif

                {{-- Filters --}}
                <div class="bg-white shadow-sm rounded-xl sm:rounded-lg p-3 sm:p-4 mb-4 sm:mb-6" x-data="{ open: false }">
                    <button class="filter-toggle" @click="open = !open" :class="{ 'open': open }">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Search Investors
                        </span>
                        <svg class="chevron w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="filter-body" :class="{ 'open': open }">
                        <form method="GET" action="{{ route('investors.index') }}" class="space-y-0">
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/email/phone…" class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm" />
                            <div class="filter-actions">
                                <button type="submit" class="bg-indigo-600 text-white">Search</button>
                                <a href="{{ route('investors.index') }}" class="bg-gray-100 text-gray-600">Reset</a>
                            </div>
                        </form>
                    </div>
                    {{-- Desktop version --}}
                    <form method="GET" action="{{ route('investors.index') }}" class="hidden sm:flex sm:flex-wrap sm:gap-3 sm:items-center">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/email/phone…" class="border border-gray-300 rounded-md px-3 py-2 text-sm flex-1 min-w-0 sm:max-w-xs" />
                        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm font-medium">Filter</button>
                        <a href="{{ route('investors.index') }}" class="text-sm text-gray-500 hover:underline">Reset</a>
                    </form>
                </div>

                {{-- Settlement explanation (desktop only) --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 hidden sm:block">
                    <p class="text-sm text-blue-800">
                        <strong>How Settlement Works:</strong> Ownership % is driven by each investor's capital contribution.
                        Expense Split % is editable and must total 100%.
                        <strong>Settlement Balance = Paid − Owed</strong>. Positive = overpaid (pool owes them). Negative = they owe the pool/manager.
                    </p>
                </div>

                {{-- Desktop table --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm whitespace-nowrap">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 text-left">Investor</th>
                                    <th class="px-4 py-3 text-left">Role</th>
                                    <th class="px-4 py-3 text-right">Capital</th>
                                    <th class="px-4 py-3 text-right">Own %</th>
                                    <th class="px-4 py-3 text-right">Herd Share</th>
                                    <th class="px-4 py-3 text-right">Profit Share</th>
                                    <th class="px-4 py-3 text-right">Split %</th>
                                    <th class="px-4 py-3 text-right">Exp Owed</th>
                                    <th class="px-4 py-3 text-right">Exp Paid</th>
                                    <th class="px-4 py-3 text-right">Settlement</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($investors as $investor)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium">
                                            <a href="{{ route('investors.show', $investor) }}" class="text-indigo-600 hover:underline">{{ $investor->name }}</a>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($investor->isManager())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Manager</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Investor</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium">{{ number_format($investor->total_capital, 0) }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($investor->share_pct, 1) }}%</td>
                                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($investor->herd_share, 0) }}</td>
                                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($investor->profit_share, 0) }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($investor->expense_split_pct, 1) }}%</td>
                                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($investor->expense_owed, 0) }}</td>
                                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($investor->expense_paid, 0) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold {{ $investor->settlement_balance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $investor->settlement_balance >= 0 ? '+' : '' }}{{ number_format($investor->settlement_balance, 0) }}
                                        </td>
                                        <td class="px-4 py-3 text-center space-x-2 whitespace-nowrap">
                                            <a href="{{ route('investors.edit', $investor) }}" class="text-indigo-600 hover:underline text-xs">Edit</a>
                                            <form method="POST" action="{{ route('investors.destroy', $investor) }}" class="inline" onsubmit="return confirm('Delete this investor?')">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 hover:underline text-xs">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="px-4 py-8 text-center text-gray-400">No investors yet. <a href="{{ route('investors.create') }}" class="text-indigo-600 hover:underline">Add one</a></td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if (count($investors) > 0)
                                <tfoot>
                                    <tr class="bg-gray-50 font-semibold">
                                        <td class="px-4 py-3" colspan="2">TOTAL</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($totalCapital, 0) }}</td>
                                        <td class="px-4 py-3 text-right">100.0%</td>
                                        <td colspan="3"></td>
                                        <td class="px-4 py-3 text-right">{{ number_format($investors->sum(fn($i) => $i->expense_owed), 0) }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($investors->sum(fn($i) => $i->expense_paid), 0) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-100">{{ $investors->withQueryString()->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
