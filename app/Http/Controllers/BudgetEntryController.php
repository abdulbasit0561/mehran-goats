<?php

namespace App\Http\Controllers;

use App\Models\BudgetEntry;
use Illuminate\Http\Request;

class BudgetEntryController extends Controller
{
    /**
     * Display the annual budget planner grid.
     */
    public function index(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $availableYears = BudgetEntry::select('year')->distinct()->orderByDesc('year')->pluck('year')->toArray();
        if (! in_array($year, $availableYears)) {
            $availableYears[] = $year;
        }
        rsort($availableYears);

        $incomeEntries  = BudgetEntry::forYear($year)->where('type', 'income')->orderBy('category')->get();
        $expenseEntries = BudgetEntry::forYear($year)->where('type', 'expense')->orderBy('category')->get();

        return view('budget.index', compact('year', 'availableYears', 'incomeEntries', 'expenseEntries'));
    }

    /**
     * Show form to create a new budget row.
     */
    public function create()
    {
        $incomeCategories  = BudgetEntry::INCOME_CATEGORIES;
        $expenseCategories = BudgetEntry::EXPENSE_CATEGORIES;

        return view('budget.create', compact('incomeCategories', 'expenseCategories'));
    }

    /**
     * Store a new budget row.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'     => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:100'],
            'year'     => ['required', 'integer', 'min:2020', 'max:2050'],
            'jan'      => ['nullable', 'numeric', 'min:0'],
            'feb'      => ['nullable', 'numeric', 'min:0'],
            'mar'      => ['nullable', 'numeric', 'min:0'],
            'apr'      => ['nullable', 'numeric', 'min:0'],
            'may'      => ['nullable', 'numeric', 'min:0'],
            'jun'      => ['nullable', 'numeric', 'min:0'],
            'jul'      => ['nullable', 'numeric', 'min:0'],
            'aug'      => ['nullable', 'numeric', 'min:0'],
            'sep'      => ['nullable', 'numeric', 'min:0'],
            'oct'      => ['nullable', 'numeric', 'min:0'],
            'nov'      => ['nullable', 'numeric', 'min:0'],
            'dec'      => ['nullable', 'numeric', 'min:0'],
        ]);

        // Fill defaults
        foreach (BudgetEntry::MONTHS as $m) {
            $validated[$m] = $validated[$m] ?? 0;
        }

        BudgetEntry::create($validated);

        return redirect()
            ->route('budget.index', ['year' => $validated['year']])
            ->with('success', "Budget row '{$validated['category']}' added.");
    }

    /**
     * Update a single cell or entire row via AJAX or form.
     */
    public function update(Request $request, BudgetEntry $budget_entry)
    {
        $validated = $request->validate([
            'category' => ['sometimes', 'string', 'max:100'],
            'jan'      => ['sometimes', 'numeric', 'min:0'],
            'feb'      => ['sometimes', 'numeric', 'min:0'],
            'mar'      => ['sometimes', 'numeric', 'min:0'],
            'apr'      => ['sometimes', 'numeric', 'min:0'],
            'may'      => ['sometimes', 'numeric', 'min:0'],
            'jun'      => ['sometimes', 'numeric', 'min:0'],
            'jul'      => ['sometimes', 'numeric', 'min:0'],
            'aug'      => ['sometimes', 'numeric', 'min:0'],
            'sep'      => ['sometimes', 'numeric', 'min:0'],
            'oct'      => ['sometimes', 'numeric', 'min:0'],
            'nov'      => ['sometimes', 'numeric', 'min:0'],
            'dec'      => ['sometimes', 'numeric', 'min:0'],
        ]);

        $budget_entry->update($validated);

        return redirect()
            ->route('budget.index', ['year' => $budget_entry->year])
            ->with('success', "Budget row '{$budget_entry->category}' updated.");
    }

    /**
     * Inline update via AJAX.
     */
    public function inlineUpdate(Request $request, BudgetEntry $budget_entry)
    {
        $validated = $request->validate([
            'month'  => ['required', 'in:jan,feb,mar,apr,may,jun,jul,aug,sep,oct,nov,dec'],
            'value'  => ['required', 'numeric', 'min:0'],
        ]);

        $budget_entry->update([$validated['month'] => $validated['value']]);

        return response()->json([
            'success'      => true,
            'annual_total' => $budget_entry->fresh()->annual_total,
        ]);
    }

    /**
     * Delete a budget row.
     */
    public function destroy(BudgetEntry $budget_entry)
    {
        $year = $budget_entry->year;
        $category = $budget_entry->category;
        $budget_entry->delete();

        return redirect()
            ->route('budget.index', ['year' => $year])
            ->with('success', "Budget row '{$category}' deleted.");
    }

    /**
     * Seed a default budget for a year with all standard categories.
     */
    public function seedYear(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));

        $categories = array_merge(
            array_map(fn ($c) => ['type' => 'income', 'category' => $c], BudgetEntry::INCOME_CATEGORIES),
            array_map(fn ($c) => ['type' => 'expense', 'category' => $c], BudgetEntry::EXPENSE_CATEGORIES)
        );

        $created = 0;
        foreach ($categories as $cat) {
            $exists = BudgetEntry::where('type', $cat['type'])
                ->where('category', $cat['category'])
                ->where('year', $year)
                ->exists();
            if (! $exists) {
                BudgetEntry::create(array_merge($cat, ['year' => $year]));
                $created++;
            }
        }

        return redirect()
            ->route('budget.index', ['year' => $year])
            ->with('success', "Seeded {$created} budget rows for {$year}.");
    }
}
