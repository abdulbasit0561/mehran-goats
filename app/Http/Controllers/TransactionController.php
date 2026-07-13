<?php

namespace App\Http\Controllers;

use App\Models\Goat;
use App\Models\Investor;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['goat', 'investor']);

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($investorId = $request->get('paid_by')) {
            $query->where('investor_id', $investorId);
        }

        if ($request->filled('from')) {
            $query->where('date', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->where('date', '<=', $request->get('to'));
        }

        $transactions = $query->latest('date')->paginate(20)->withQueryString();

        // For filters
        $categories = collect(array_merge(Transaction::EXPENSE_CATEGORIES, Transaction::INCOME_CATEGORIES))->unique()->sort()->values();
        $investors  = Investor::orderBy('name')->get(['id', 'name']);

        // --- Expense Summary ---
        $totalExpenses    = (float) Transaction::expense()->sum('amount');
        $feedTotal        = (float) Transaction::expense()->where('category', 'Feed & Fodder')->sum('amount');
        $vetTotal         = (float) Transaction::expense()->where('category', 'Veterinary & Medicine')->sum('amount');
        $laborTotal       = (float) Transaction::expense()->where('category', 'Labor / Wages')->sum('amount');

        $summary = [
            'total'   => $totalExpenses,
            'feed'    => $feedTotal,
            'vet'     => $vetTotal,
            'labor'   => $laborTotal,
        ];

        return view('transactions.index', compact('transactions', 'categories', 'investors', 'summary'));
    }

    /**
     * Server-side DataTables AJAX endpoint for Transactions.
     */
    public function data(Request $request)
    {
        $query = Transaction::with(['goat', 'investor'])->select('transactions.*');

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value'] ?? null) {
                    $query->where(function ($q) use ($search) {
                        $q->where('category', 'like', "%{$search}%")
                          ->orWhere('notes', 'like', "%{$search}%")
                          ->orWhere('type', 'like', "%{$search}%")
                          ->orWhereHas('investor', fn ($inv) => $inv->where('name', 'like', "%{$search}%"));
                    });
                }
                if ($type = $request->get('type')) {
                    $query->where('type', $type);
                }
                if ($category = $request->get('category')) {
                    $query->where('category', $category);
                }
                if ($investorId = $request->get('paid_by')) {
                    $query->where('investor_id', $investorId);
                }
                if ($request->filled('from')) {
                    $query->where('date', '>=', $request->get('from'));
                }
                if ($request->filled('to')) {
                    $query->where('date', '<=', $request->get('to'));
                }
            })
            ->editColumn('date', fn (Transaction $t) => $t->date->format('M d, Y'))
            ->editColumn('type', function (Transaction $t) {
                $cls = $t->type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $cls . '">' . ucfirst($t->type) . '</span>';
            })
            ->editColumn('category', fn (Transaction $t) => e($t->category))
            ->addColumn('notes', fn (Transaction $t) => '<span class="text-gray-500">' . e($t->notes ?? '—') . '</span>')
            ->addColumn('paid_by', fn (Transaction $t) => e($t->investor?->name ?? '—'))
            ->addColumn('payment_method', fn (Transaction $t) => $t->payment_method ? '<span class="text-xs text-gray-500">' . e(str_replace('_', ' ', ucfirst($t->payment_method))) . '</span>' : '—')
            ->addColumn('is_recurring', function (Transaction $t) {
                if ($t->is_recurring) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Yes</span>';
                }
                return '<span class="text-gray-300">—</span>';
            })
            ->addColumn('budget_amount', fn (Transaction $t) => $t->budget_amount ? number_format($t->budget_amount, 0) : '—')
            ->addColumn('amount', function (Transaction $t) {
                $cls = $t->type === 'income' ? 'text-green-600' : 'text-red-600';
                $sign = $t->type === 'income' ? '+' : '-';
                return '<span class="font-medium ' . $cls . '">' . $sign . number_format($t->amount, 0) . '</span>';
            })
            ->addColumn('variance', function (Transaction $t) {
                if ($t->variance === null) return '—';
                $cls = $t->variance >= 0 ? 'text-green-600' : 'text-red-600';
                return '<span class="text-xs ' . $cls . '">' . number_format($t->variance, 0) . '</span>';
            })
            ->addColumn('actions', function (Transaction $t) {
                return '<a href="' . route('transactions.edit', $t) . '" class="text-indigo-600 hover:underline text-xs">Edit</a> '
                    . '<form method="POST" action="' . route('transactions.destroy', $t) . '" class="inline" onsubmit="return confirm(\'Delete this transaction?\')">'
                    . csrf_field() . method_field('DELETE')
                    . '<button class="text-red-600 hover:underline text-xs">Delete</button></form>';
            })
            ->rawColumns(['type', 'notes', 'payment_method', 'is_recurring', 'amount', 'variance', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $goats     = Goat::orderBy('tag_id')->get(['id', 'tag_id', 'name']);
        $investors = Investor::orderBy('name')->get(['id', 'name']);

        $incomeCategories  = Transaction::INCOME_CATEGORIES;
        $expenseCategories = Transaction::EXPENSE_CATEGORIES;

        return view('transactions.create', compact('goats', 'investors', 'incomeCategories', 'expenseCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'           => ['required', 'in:income,expense'],
            'category'       => ['required', 'string', 'max:100'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'date'           => ['required', 'date'],
            'goat_id'        => ['nullable', 'exists:goats,id'],
            'investor_id'    => ['nullable', 'exists:investors,id'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,check,other'],
            'is_recurring'   => ['nullable', 'boolean'],
            'budget_amount'  => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string'],
        ]);

        $validated['is_recurring'] = $request->boolean('is_recurring');

        Transaction::create($validated);

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction recorded successfully.');
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['goat', 'investor']);

        return view('transactions.show', compact('transaction'));
    }

    public function edit(Transaction $transaction)
    {
        $goats     = Goat::orderBy('tag_id')->get(['id', 'tag_id', 'name']);
        $investors = Investor::orderBy('name')->get(['id', 'name']);
        $incomeCategories  = Transaction::INCOME_CATEGORIES;
        $expenseCategories = Transaction::EXPENSE_CATEGORIES;

        return view('transactions.edit', compact('transaction', 'goats', 'investors', 'incomeCategories', 'expenseCategories'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'type'           => ['required', 'in:income,expense'],
            'category'       => ['required', 'string', 'max:100'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'date'           => ['required', 'date'],
            'goat_id'        => ['nullable', 'exists:goats,id'],
            'investor_id'    => ['nullable', 'exists:investors,id'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,check,other'],
            'is_recurring'   => ['nullable', 'boolean'],
            'budget_amount'  => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string'],
        ]);

        $validated['is_recurring'] = $request->boolean('is_recurring');

        $transaction->update($validated);

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction updated.');
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction deleted.');
    }
}
