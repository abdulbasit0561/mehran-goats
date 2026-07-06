<?php

namespace App\Http\Controllers;

use App\Models\Goat;
use App\Models\Investor;
use App\Models\Transaction;
use Illuminate\Http\Request;

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
