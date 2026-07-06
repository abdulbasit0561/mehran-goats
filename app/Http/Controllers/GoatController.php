<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\Caretaker;
use App\Models\Goat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GoatController extends Controller
{
    public function index(Request $request)
    {
        $query = Goat::with(['breed', 'birthedRecords' => function ($q) {
            $q->whereIn('pregnancy_status', ['bred', 'pregnant'])->latest('breeding_date');
        }]);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('tag_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($breedId = $request->get('breed')) {
            $query->where('breed_id', $breedId);
        }

        // Pregnancy filter: show only pregnant/bred does, or only open (non-pregnant) does
        if ($request->get('pregnant') === 'yes') {
            $query->whereHas('birthedRecords', function ($q) {
                $q->whereIn('pregnancy_status', ['bred', 'pregnant']);
            });
        } elseif ($request->get('pregnant') === 'open') {
            $query->whereDoesntHave('birthedRecords', function ($q) {
                $q->whereIn('pregnancy_status', ['bred', 'pregnant']);
            });
        }

        $goats = $query->latest()->paginate(15)->withQueryString();

        // --- Livestock Financial & Herd Summary ---
        $purchasedCount = Goat::count();
        $inHerdCount    = Goat::whereNotIn('status', ['sold', 'deceased'])->count();
        $soldCount      = Goat::where('status', 'sold')->count();
        $diedCount      = Goat::where('status', 'deceased')->count();
        $mortalityRate  = $purchasedCount > 0 ? round($diedCount / $purchasedCount * 100, 1) : 0;

        $totalPurchaseCost = (float) Goat::sum('purchase_price');
        $totalSalesRevenue = (float) Goat::where('status', 'sold')->sum('sale_price');
        $realizedProfit    = $totalSalesRevenue - (float) Goat::where('status', 'sold')->sum('purchase_price');
        $currentHerdValue  = (float) Goat::whereNotIn('status', ['sold', 'deceased'])->sum('estimated_value');

        $summary = [
            'purchased_count'   => $purchasedCount,
            'in_herd_count'     => $inHerdCount,
            'sold_count'        => $soldCount,
            'died_count'        => $diedCount,
            'mortality_rate'    => $mortalityRate,
            'total_purchase'    => $totalPurchaseCost,
            'total_sales'       => $totalSalesRevenue,
            'realized_profit'   => $realizedProfit,
            'current_herd_value'=> $currentHerdValue,
            'pregnant_count'    => BreedingRecord::pregnant()->count(),
            'kids_born'         => Goat::where('birth_type', 'farm_born')->count(),
        ];

        $breeds = Breed::orderBy('name')->pluck('name', 'id');

        return view('goats.index', compact('goats', 'summary', 'breeds'));
    }

    public function create()
    {
        $breeds = Breed::orderBy('name')->pluck('name', 'id');
        $caretakers = Caretaker::where('active', true)->orderBy('name')->pluck('name', 'id');

        return view('goats.create', compact('breeds', 'caretakers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tag_id'           => ['required', 'string', 'max:100', 'unique:goats,tag_id'],
            'name'             => ['nullable', 'string', 'max:100'],
            'breed_id'         => ['required', 'exists:breeds,id'],
            'caretaker_id'     => ['nullable', 'exists:caretakers,id'],
            'gender'           => ['required', 'in:male,female'],
            'dob'              => ['nullable', 'date', 'before_or_equal:today'],
            'weight_kg'        => ['nullable', 'numeric', 'min:0'],
            'purchase_date'    => ['nullable', 'date'],
            'purchase_weight'  => ['nullable', 'numeric', 'min:0'],
            'vendor'           => ['nullable', 'string', 'max:255'],
            'status'           => ['required', 'in:kid,grower,breeder,sold,deceased'],
            'purchase_price'   => ['nullable', 'numeric', 'min:0'],
            'sale_price'       => ['nullable', 'numeric', 'min:0'],
            'sale_date'        => ['nullable', 'date'],
            'sale_weight'      => ['nullable', 'numeric', 'min:0'],
            'estimated_value'  => ['nullable', 'numeric', 'min:0'],
            'photo'            => ['nullable', 'image', 'max:2048'],
            'notes'            => ['nullable', 'string'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('goats', 'public');
        }

        Goat::create($validated);

        return redirect()
            ->route('goats.index')
            ->with('success', "Goat {$validated['tag_id']} created successfully.");
    }

    public function show(Goat $goat)
    {
        $goat->load(['breed', 'caretaker', 'sire.breed', 'dam.breed', 'healthRecords', 'siredRecords.dam', 'birthedRecords.sire', 'birthedRecords.kids', 'ownershipUnits.investor']);

        return view('goats.show', compact('goat'));
    }

    public function edit(Goat $goat)
    {
        $breeds = Breed::orderBy('name')->pluck('name', 'id');
        $caretakers = Caretaker::where('active', true)->orderBy('name')->pluck('name', 'id');

        return view('goats.edit', compact('goat', 'breeds', 'caretakers'));
    }

    public function update(Request $request, Goat $goat)
    {
        $validated = $request->validate([
            'tag_id'           => ['required', 'string', 'max:100', 'unique:goats,tag_id,' . $goat->id],
            'name'             => ['nullable', 'string', 'max:100'],
            'breed_id'         => ['required', 'exists:breeds,id'],
            'caretaker_id'     => ['nullable', 'exists:caretakers,id'],
            'gender'           => ['required', 'in:male,female'],
            'dob'              => ['nullable', 'date', 'before_or_equal:today'],
            'weight_kg'        => ['nullable', 'numeric', 'min:0'],
            'purchase_date'    => ['nullable', 'date'],
            'purchase_weight'  => ['nullable', 'numeric', 'min:0'],
            'vendor'           => ['nullable', 'string', 'max:255'],
            'status'           => ['required', 'in:kid,grower,breeder,sold,deceased'],
            'purchase_price'   => ['nullable', 'numeric', 'min:0'],
            'sale_price'       => ['nullable', 'numeric', 'min:0'],
            'sale_date'        => ['nullable', 'date'],
            'sale_weight'      => ['nullable', 'numeric', 'min:0'],
            'estimated_value'  => ['nullable', 'numeric', 'min:0'],
            'photo'            => ['nullable', 'image', 'max:2048'],
            'notes'            => ['nullable', 'string'],
        ]);

        if ($request->hasFile('photo')) {
            if ($goat->photo) {
                Storage::disk('public')->delete($goat->photo);
            }
            $validated['photo'] = $request->file('photo')->store('goats', 'public');
        }

        $goat->update($validated);

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', "Goat {$goat->tag_id} updated successfully.");
    }

    public function destroy(Goat $goat)
    {
        $tagId = $goat->tag_id;
        $goat->delete();

        return redirect()
            ->route('goats.index')
            ->with('success', "Goat {$tagId} deleted.");
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:goats,id'],
        ]);

        $count = Goat::destroy($validated['ids']);

        return redirect()
            ->route('goats.index')
            ->with('success', "{$count} goat(s) deleted.");
    }
}
