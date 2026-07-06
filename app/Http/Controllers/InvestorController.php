<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use Illuminate\Http\Request;

class InvestorController extends Controller
{
    public function index(Request $request)
    {
        $query = Investor::with('investments');

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $investors = $query->latest()->paginate(15)->withQueryString();

        // Total capital across the farm for share % computation
        $totalCapital = (float) \DB::table('investments')->sum('amount');
        $totalExpenseSplit = (float) Investor::sum('expense_split_pct');

        return view('investors.index', compact('investors', 'totalCapital', 'totalExpenseSplit'));
    }

    public function create()
    {
        return view('investors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'role'             => ['required', 'in:investor,manager'],
            'expense_split_pct'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'email'            => ['nullable', 'email', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'address'          => ['nullable', 'string', 'max:500'],
            'notes'            => ['nullable', 'string'],
        ]);

        Investor::create($validated);

        return redirect()
            ->route('investors.index')
            ->with('success', "Investor {$validated['name']} created.");
    }

    public function show(Investor $investor)
    {
        $investor->load(['investments' => fn ($q) => $q->latest('date'),
                         'ownershipUnits.goat',
                         'transactions' => fn ($q) => $q->latest('date')->limit(20),
                         'profitDistributions']);

        return view('investors.show', compact('investor'));
    }

    public function edit(Investor $investor)
    {
        return view('investors.edit', compact('investor'));
    }

    public function update(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'role'             => ['required', 'in:investor,manager'],
            'expense_split_pct'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'email'            => ['nullable', 'email', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'address'          => ['nullable', 'string', 'max:500'],
            'notes'            => ['nullable', 'string'],
        ]);

        $investor->update($validated);

        return redirect()
            ->route('investors.show', $investor)
            ->with('success', "Investor {$investor->name} updated.");
    }

    public function destroy(Investor $investor)
    {
        $name = $investor->name;
        $investor->delete();

        return redirect()
            ->route('investors.index')
            ->with('success', "Investor {$name} deleted.");
    }
}
