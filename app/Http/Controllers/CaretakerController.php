<?php

namespace App\Http\Controllers;

use App\Models\Caretaker;
use Illuminate\Http\Request;

class CaretakerController extends Controller
{
    public function index()
    {
        $caretakers = Caretaker::orderByDesc('active')->orderBy('name')->get();

        $totalKidsBorn = (int) \App\Models\BreedingRecord::whereNotNull('kidding_date')
            ->where('litter_size', '>', 0)
            ->sum('litter_size');

        return view('caretakers.index', compact('caretakers', 'totalKidsBorn'));
    }

    public function create()
    {
        return view('caretakers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'every_nth_kid' => ['required', 'integer', 'min:1', 'max:100'],
            'start_date'    => ['nullable', 'date'],
            'active'        => ['boolean'],
            'notes'         => ['nullable', 'string'],
        ]);

        $validated['active'] = $request->boolean('active');

        Caretaker::create($validated);

        return redirect()
            ->route('caretakers.index')
            ->with('success', 'Caretaker added successfully.');
    }

    public function show(Caretaker $caretaker)
    {
        $caretaker->load(['goats.breed']);
        $ledger = $caretaker->birthLedger();
        $assignedGoats = $caretaker->goats;

        return view('caretakers.show', compact('caretaker', 'ledger', 'assignedGoats'));
    }

    public function edit(Caretaker $caretaker)
    {
        return view('caretakers.edit', compact('caretaker'));
    }

    public function update(Request $request, Caretaker $caretaker)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'every_nth_kid' => ['required', 'integer', 'min:1', 'max:100'],
            'start_date'    => ['nullable', 'date'],
            'active'        => ['boolean'],
            'notes'         => ['nullable', 'string'],
        ]);

        $validated['active'] = $request->boolean('active');

        $caretaker->update($validated);

        return redirect()
            ->route('caretakers.show', $caretaker)
            ->with('success', 'Caretaker updated successfully.');
    }

    public function destroy(Caretaker $caretaker)
    {
        $name = $caretaker->name;
        $caretaker->delete();

        return redirect()
            ->route('caretakers.index')
            ->with('success', "Caretaker {$name} deleted.");
    }
}
