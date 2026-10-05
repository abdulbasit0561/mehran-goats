<?php

namespace App\Http\Controllers;

use App\Models\Goat;
use App\Models\WeightLog;
use Illuminate\Http\Request;

class WeightLogController extends Controller
{
    public function store(Request $request, Goat $goat)
    {
        $validated = $request->validate([
            'weight_kg' => ['required', 'numeric', 'gt:0', 'max:300'],
            'logged_at' => ['nullable', 'date', 'before_or_equal:today'],
            'notes'     => ['nullable', 'string', 'max:1000'],
        ]);

        $goat->weightLogs()->create([
            'weight_kg' => $validated['weight_kg'],
            'logged_at' => $validated['logged_at'] ?? now()->format('Y-m-d'),
            'notes'     => $validated['notes'] ?? null,
        ]);

        // Keep the goat's current-weight snapshot in sync with the latest log
        $goat->syncWeightFromLogs();

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', 'Weight recorded.');
    }

    public function destroy(WeightLog $weight_log)
    {
        $goat = $weight_log->goat;
        $weight_log->delete();

        $goat->syncWeightFromLogs();

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', 'Weight entry deleted.');
    }
}
