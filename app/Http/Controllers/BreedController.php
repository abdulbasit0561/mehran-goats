<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use Illuminate\Http\Request;

class BreedController extends Controller
{
    public function index()
    {
        $breeds = Breed::withCount([
            'goats',
            'goats as male_goats_count'   => fn ($q) => $q->where('gender', 'male'),
            'goats as female_goats_count' => fn ($q) => $q->where('gender', 'female'),
        ])->orderBy('name')->get();

        return view('breeds.index', compact('breeds'));
    }

    public function create()
    {
        return view('breeds.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:breeds,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Breed::create($validated);

        return redirect()
            ->route('breeds.index')
            ->with('success', "Breed \"{$validated['name']}\" created successfully.");
    }

    public function edit(Breed $breed)
    {
        $breed->loadCount('goats');

        return view('breeds.edit', compact('breed'));
    }

    public function update(Request $request, Breed $breed)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:breeds,name,' . $breed->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $breed->update($validated);

        return redirect()
            ->route('breeds.index')
            ->with('success', "Breed \"{$breed->name}\" updated successfully.");
    }

    public function destroy(Breed $breed)
    {
        $goatCount = $breed->goats()->count();

        if ($goatCount > 0) {
            return redirect()
                ->route('breeds.index')
                ->with('error', "Cannot delete \"{$breed->name}\" — {$goatCount} goat(s) are assigned to this breed. Reassign or delete those goats first.");
        }

        $name = $breed->name;
        $breed->delete();

        return redirect()
            ->route('breeds.index')
            ->with('success', "Breed \"{$name}\" deleted.");
    }
}
