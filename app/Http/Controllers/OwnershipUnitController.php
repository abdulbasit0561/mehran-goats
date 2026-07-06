<?php

namespace App\Http\Controllers;

use App\Models\Goat;
use App\Models\Investor;
use App\Models\OwnershipUnit;
use Illuminate\Http\Request;

class OwnershipUnitController extends Controller
{
    public function index(Investor $investor)
    {
        $units = $investor->ownershipUnits()->with('goat')->latest()->paginate(20);

        return view('ownership.index', compact('investor', 'units'));
    }

    public function create(Investor $investor)
    {
        $goats = Goat::whereNotIn('status', ['sold', 'deceased'])->orderBy('tag_id')->get(['id', 'tag_id', 'name']);

        return view('ownership.create', compact('investor', 'goats'));
    }

    public function store(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'goat_id'   => ['nullable', 'exists:goats,id'],
            'share_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $investor->ownershipUnits()->create($validated);

        return redirect()
            ->route('investors.show', $investor)
            ->with('success', 'Ownership record added.');
    }

    public function destroy(OwnershipUnit $ownershipUnit)
    {
        $investorId = $ownershipUnit->investor_id;
        $ownershipUnit->delete();

        return redirect()
            ->route('investors.show', $investorId)
            ->with('success', 'Ownership record deleted.');
    }
}
