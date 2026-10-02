<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\Caretaker;
use App\Models\DeathRecord;
use App\Models\Goat;
use App\Models\GoatImage;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class GoatController extends Controller
{
    public function index(Request $request)
    {
        $query = Goat::with(['breed', 'sale' => function ($q) {
            $q->select('id', 'goat_id');
        }, 'birthedRecords' => function ($q) {
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
        $currentHerdValue  = (float) Goat::whereNotIn('status', ['sold', 'deceased'])
                                ->selectRaw('COALESCE(SUM(CASE WHEN estimated_value > 0 THEN estimated_value ELSE purchase_price END), 0) as total')
                                ->value('total');

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

    /**
     * Server-side DataTables AJAX endpoint for Goats.
     */
    public function data(Request $request)
    {
        $query = Goat::with(['breed', 'birthedRecords' => function ($q) {
            $q->whereIn('pregnancy_status', ['bred', 'pregnant'])->latest('breeding_date');
        }])->select('goats.*');

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value'] ?? null) {
                    $query->where(function ($q) use ($search) {
                        $q->where('tag_id', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%")
                          ->orWhere('vendor', 'like', "%{$search}%")
                          ->orWhereHas('breed', fn ($b) => $b->where('name', 'like', "%{$search}%"));
                    });
                }
                if ($status = $request->get('status')) {
                    $query->where('status', $status);
                }
                if ($breedId = $request->get('breed')) {
                    $query->where('breed_id', $breedId);
                }
                if ($request->get('pregnant') === 'yes') {
                    $query->whereHas('birthedRecords', fn ($q) => $q->whereIn('pregnancy_status', ['bred', 'pregnant']));
                } elseif ($request->get('pregnant') === 'open') {
                    $query->whereDoesntHave('birthedRecords', fn ($q) => $q->whereIn('pregnancy_status', ['bred', 'pregnant']));
                }
            })
            ->editColumn('tag_id', fn (Goat $goat) => '<a href="' . route('goats.show', $goat) . '" class="text-indigo-600 font-medium">' . e($goat->tag_id) . '</a>')
            ->addColumn('breed_name', fn (Goat $goat) => e($goat->breed?->name ?? '—'))
            ->addColumn('status_badge', function (Goat $goat) {
                $colors = ['sold' => 'bg-blue-100 text-blue-800', 'deceased' => 'bg-gray-200 text-gray-700', 'breeder' => 'bg-green-100 text-green-800'];
                $cls = $colors[$goat->status] ?? 'bg-yellow-100 text-yellow-800';
                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $cls . '">' . ucfirst($goat->status) . '</span>';
            })
            ->addColumn('breeding', function (Goat $goat) {
                if ($goat->gender !== 'female' || in_array($goat->status, ['sold', 'deceased'])) return '<span class="text-gray-300 text-xs">—</span>';
                $preg = $goat->activePregnancy();
                if ($preg) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-pink-100 text-pink-700">🤰 ' . ($preg->isConfirmed() ? 'Pregnant' : 'Bred') . ' (' . $preg->days_pregnant . 'd)</span>';
                }
                return '<a href="' . route('goats.breeding-records.create', $goat) . '" class="text-xs text-pink-600 hover:underline font-medium">🤰 Mark Bred</a>';
            })
            ->editColumn('purchase_date', fn (Goat $goat) => $goat->purchase_date?->format('M d, Y') ?? '—')
            ->editColumn('vendor', fn (Goat $goat) => e($goat->vendor ?? '—'))
            ->editColumn('purchase_price', fn (Goat $goat) => number_format($goat->purchase_price ?? 0, 0))
            ->addColumn('estimated_value', fn (Goat $goat) => number_format($goat->current_value, 0))
            ->addColumn('gain_loss', function (Goat $goat) {
                if ($goat->status !== 'sold') return '—';
                $cls = $goat->realized_gain_loss > 0 ? 'text-green-600' : ($goat->realized_gain_loss < 0 ? 'text-red-600' : 'text-gray-400');
                $sign = $goat->realized_gain_loss >= 0 ? '+' : '';
                return '<span class="font-medium ' . $cls . '">' . $sign . number_format($goat->realized_gain_loss, 0) . '</span>';
            })
            ->addColumn('days_held', fn (Goat $goat) => $goat->days_held ?? '—')
            ->addColumn('actions', function (Goat $goat) {
                return '<a href="' . route('goats.edit', $goat) . '" class="text-indigo-600 hover:underline text-xs">Edit</a> '
                    . '<form method="POST" action="' . route('goats.destroy', $goat) . '" class="inline" onsubmit="return confirm(\'Delete this goat?\')">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="submit" class="text-red-600 hover:underline text-xs">Delete</button></form>';
            })
            ->rawColumns(['tag_id', 'status_badge', 'breeding', 'gain_loss', 'actions'])
            ->make(true);
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

        $goat = Goat::create($validated);

        // Sync profile photo into the gallery
        if ($goat->photo) {
            $this->syncPhotoToGallery($goat);
        }

        return redirect()
            ->route('goats.index')
            ->with('success', "Goat {$validated['tag_id']} created successfully.");
    }

    public function show(Goat $goat)
    {
        $goat->load(['breed', 'caretaker', 'sire.breed', 'dam.breed', 'healthRecords', 'siredRecords.dam', 'birthedRecords.sire', 'birthedRecords.kids', 'ownershipUnits.investor', 'deathRecord', 'images', 'sale.customer', 'sale.seller']);

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

        // When a goat is marked sold with a sale price, record the full sale
        // (status + exactly one "Goat Sales" income transaction) through the
        // shared SaleService. Buyer/seller are unknown on this legacy path.
        if ($goat->status === 'sold' && (float) $goat->sale_price > 0) {
            $existing = $goat->sale()
                ->where('sale_price', $goat->sale_price)
                ->where('sale_date', $goat->sale_date?->format('Y-m-d'))
                ->exists();

            if (! $existing) {
                app(\App\Services\SaleService::class)->recordSale($goat, [
                    'customer_id'        => null,
                    'seller_investor_id' => null,
                    'sale_date'          => $goat->sale_date ?? now()->format('Y-m-d'),
                    'sale_price'         => (float) $goat->sale_price,
                    'sale_weight'        => $goat->sale_weight,
                    'payment_method'     => 'cash',
                    'notes'              => 'Marked sold via goat edit',
                ]);
            } else {
                // Keep an existing sale's figures in sync with the goat edit.
                $goat->sale()->update([
                    'sale_price' => $goat->sale_price,
                    'sale_date'  => $goat->sale_date,
                ]);
            }
        }

        // Sync new profile photo into the gallery
        if (isset($validated['photo'])) {
            $this->syncPhotoToGallery($goat);
        }

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', "Goat {$goat->tag_id} updated successfully.");
    }

    //---------------------------------------------------------------------
    // DECLARE DEAD — record a death and set status to 'deceased'
    //---------------------------------------------------------------------

    /** Show the death declaration form for a goat. */
    public function declareDead(Goat $goat)
    {
        if ($goat->status === 'deceased') {
            return redirect()
                ->route('goats.show', $goat)
                ->with('error', "{$goat->tag_id} has already been declared dead.");
        }

        return view('goats.declare-dead', compact('goat'));
    }

    /** Validate + store the death record and set the goat's status to deceased. */
    public function storeDeath(Request $request, Goat $goat)
    {
        if ($goat->status === 'deceased') {
            return redirect()
                ->route('goats.show', $goat)
                ->with('error', "{$goat->tag_id} has already been declared dead.");
        }

        $validated = $request->validate([
            'death_date'      => ['required', 'date', 'before_or_equal:today'],
            'cause'           => ['nullable', 'string', 'max:255'],
            'disposal_method' => ['required', 'in:sold_to_butcher,buried,composted,other'],
            'disposal_value'  => ['nullable', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($goat, $validated) {
            DeathRecord::create([
                'goat_id'         => $goat->id,
                'death_date'      => $validated['death_date'],
                'cause'           => $validated['cause'] ?? null,
                'disposal_method' => $validated['disposal_method'],
                'disposal_value'  => $validated['disposal_value'] ?? 0,
                'notes'           => $validated['notes'] ?? null,
            ]);

            $goat->update(['status' => 'deceased']);
        });

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', "{$goat->tag_id} marked as deceased. Death record saved.");
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

    //---------------------------------------------------------------------
    // HELPERS
    //---------------------------------------------------------------------

    /**
     * Create a gallery entry for the goat's current profile photo.
     * Captures current weight and age context for the gallery timeline.
     */
    private function syncPhotoToGallery(Goat $goat): void
    {
        GoatImage::create([
            'goat_id'     => $goat->id,
            'image_path'  => $goat->photo,
            'age_months'  => $goat->age_in_months ?? 0,
            'weight_kg'   => $goat->weight_kg ?? 0,
            'taken_at'    => now()->toDateString(),
            'notes'       => 'Profile photo',
        ]);
    }
}
