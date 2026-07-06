<?php

namespace App\Http\Controllers;

use App\Models\Goat;
use App\Models\HealthRecord;
use Illuminate\Http\Request;

class HealthRecordController extends Controller
{
    public function index(Goat $goat)
    {
        $records = $goat->healthRecords()->paginate(20);

        return view('health-records.index', compact('goat', 'records'));
    }

    public function create(Goat $goat)
    {
        return view('health-records.create', compact('goat'));
    }

    public function store(Request $request, Goat $goat)
    {
        $validated = $request->validate([
            'type'  => ['required', 'in:vaccination,treatment,checkup,other'],
            'date'  => ['required', 'date', 'before_or_equal:today'],
            'cost'  => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $goat->healthRecords()->create([
            'type'  => $validated['type'],
            'date'  => $validated['date'],
            'cost'  => $validated['cost'] ?? 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', 'Health record added.');
    }

    public function destroy(HealthRecord $health_record)
    {
        $goatId = $health_record->goat_id;
        $health_record->delete();

        return redirect()
            ->route('goats.show', $goatId)
            ->with('success', 'Health record deleted.');
    }
}
