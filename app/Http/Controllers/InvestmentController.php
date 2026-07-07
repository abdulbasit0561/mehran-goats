<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Investor;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'investor_id' => ['required', 'exists:investors,id'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'date'        => ['required', 'date', 'before_or_equal:today'],
            'notes'       => ['nullable', 'string'],
        ]);

        $investor = Investor::findOrFail($validated['investor_id']);
        $investor->investments()->create([
            'amount' => $validated['amount'],
            'date'   => $validated['date'],
            'notes'  => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('investors.index')
            ->with('success', "Investment of {$validated['amount']} recorded for {$investor->name}.");
    }

    public function index(Investor $investor)
    {
        $investments = $investor->investments()->latest('date')->paginate(20);

        return view('investments.index', compact('investor', 'investments'));
    }

    public function create(Investor $investor)
    {
        return view('investments.create', compact('investor'));
    }

    public function store(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date'   => ['required', 'date', 'before_or_equal:today'],
            'notes'  => ['nullable', 'string'],
        ]);

        $investor->investments()->create($validated);

        return redirect()
            ->route('investors.show', $investor)
            ->with('success', 'Investment recorded successfully.');
    }

    public function destroy(Investment $investment)
    {
        $investorId = $investment->investor_id;
        $investment->delete();

        return redirect()
            ->route('investors.show', $investorId)
            ->with('success', 'Investment deleted.');
    }
}
