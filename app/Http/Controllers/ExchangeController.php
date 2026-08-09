<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use App\Models\Exchange;
use App\Models\Goat;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class ExchangeController extends Controller
{
    /**
     * Display exchange history with summary stats.
     */
    public function index(Request $request)
    {
        $query = Exchange::with(['outgoingGoat.breed', 'incomingGoat.breed', 'breed']);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('counterparty', 'like', "%{$search}%")
                  ->orWhere('incoming_tag_id', 'like', "%{$search}%")
                  ->orWhereHas('outgoingGoat', fn ($g) => $g->where('tag_id', 'like', "%{$search}%"));
            });
        }

        $exchanges = $query->latest('exchange_date')->paginate(15)->withQueryString();

        // --- Summary ---
        $totalExchanges   = Exchange::count();
        $totalCashPaid    = (float) Exchange::where('cash_direction', 'paid')->sum('cash_amount');
        $totalCashReceived= (float) Exchange::where('cash_direction', 'received')->sum('cash_amount');
        $netHerdValueChange = (float) Exchange::sum(DB::raw('incoming_value - outgoing_value'));

        $summary = [
            'total_exchanges'     => $totalExchanges,
            'total_cash_paid'     => $totalCashPaid,
            'total_cash_received' => $totalCashReceived,
            'net_herd_value_change' => $netHerdValueChange,
        ];

        return view('exchanges.index', compact('exchanges', 'summary'));
    }

    /**
     * Server-side DataTables AJAX endpoint for exchanges.
     */
    public function data(Request $request)
    {
        $query = Exchange::with(['outgoingGoat.breed', 'incomingGoat.breed', 'breed'])
            ->select('exchanges.*');

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value'] ?? null) {
                    $query->where(function ($q) use ($search) {
                        $q->where('counterparty', 'like', "%{$search}%")
                          ->orWhere('incoming_tag_id', 'like', "%{$search}%")
                          ->orWhereHas('outgoingGoat', fn ($g) => $g->where('tag_id', 'like', "%{$search}%"));
                    });
                }
            })
            ->editColumn('exchange_date', fn (Exchange $e) => $e->exchange_date?->format('M d, Y'))
            ->addColumn('outgoing', function (Exchange $e) {
                if ($e->outgoingGoat) {
                    return '<a href="' . route('goats.show', $e->outgoingGoat) . '" class="text-indigo-600 font-medium">' . e($e->outgoingGoat->tag_id) . '</a>'
                        . '<span class="text-gray-400 text-xs ml-2">' . number_format($e->outgoing_value, 0) . '</span>';
                }
                return '<span class="text-gray-400">—</span>';
            })
            ->addColumn('incoming', function (Exchange $e) {
                if ($e->incomingGoat) {
                    return '<a href="' . route('goats.show', $e->incomingGoat) . '" class="text-indigo-600 font-medium">' . e($e->incomingGoat->tag_id) . '</a>'
                        . '<span class="text-gray-400 text-xs ml-2">' . number_format($e->incoming_value, 0) . '</span>';
                }
                return e($e->incoming_tag_id) . '<span class="text-gray-400 text-xs ml-2">' . number_format($e->incoming_value, 0) . '</span>';
            })
            ->addColumn('cash', function (Exchange $e) {
                if ($e->cash_direction === 'none' || (float) $e->cash_amount === 0.0) {
                    return '<span class="text-gray-400 text-xs">No cash</span>';
                }
                if ($e->cash_direction === 'paid') {
                    return '<span class="font-medium text-red-600">-' . number_format($e->cash_amount, 0) . '</span>';
                }
                return '<span class="font-medium text-green-600">+' . number_format($e->cash_amount, 0) . '</span>';
            })
            ->editColumn('counterparty', fn (Exchange $e) => e($e->counterparty ?? '—'))
            ->addColumn('actions', function (Exchange $e) {
                return '<a href="' . route('exchanges.show', $e) . '" class="text-indigo-600 hover:underline text-xs">View</a>';
            })
            ->rawColumns(['outgoing', 'incoming', 'cash', 'actions'])
            ->make(true);
    }

    /**
     * Show the exchange creation form.
     */
    public function create()
    {
        // Only goats currently in the herd can be exchanged out
        $herdGoats = Goat::whereNotIn('status', ['sold', 'deceased'])
            ->orderBy('tag_id')
            ->get(['id', 'tag_id', 'name', 'breed_id', 'gender', 'weight_kg', 'purchase_price', 'estimated_value']);

        $herdGoats->load('breed');

        $breeds = Breed::orderBy('name')->pluck('name', 'id');

        return view('exchanges.create', compact('herdGoats', 'breeds'));
    }

    /**
     * Process the exchange atomically.
     *
     * Steps (all inside DB::transaction):
     * 1. Mark outgoing goat as sold at the agreed value
     * 2. Create income transaction for the outgoing goat value
     * 3. Create the new incoming goat record
     * 4. Record the cash difference as expense or income
     * 5. Create the exchange record linking everything
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'exchange_date'     => ['required', 'date', 'before_or_equal:today'],
            'outgoing_goat_id'  => ['required', 'exists:goats,id'],
            'outgoing_value'    => ['required', 'numeric', 'min:0'],
            'incoming_tag_id'   => ['required', 'string', 'max:100', 'unique:goats,tag_id'],
            'breed_id'          => ['required', 'exists:breeds,id'],
            'incoming_gender'   => ['required', 'in:male,female'],
            'incoming_weight_kg'=> ['nullable', 'numeric', 'min:0'],
            'incoming_value'    => ['required', 'numeric', 'min:0'],
            'counterparty'      => ['nullable', 'string', 'max:255'],
            'payment_method'    => ['nullable', 'in:cash,bank_transfer,check,other'],
            'notes'             => ['nullable', 'string'],
        ]);

        $outgoingGoat = Goat::findOrFail($validated['outgoing_goat_id']);

        // Guard: outgoing goat must still be in the herd
        if (in_array($outgoingGoat->status, ['sold', 'deceased'])) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', "Goat {$outgoingGoat->tag_id} is already sold or deceased and cannot be exchanged.");
        }

        $outgoingValue = (float) $validated['outgoing_value'];
        $incomingValue = (float) $validated['incoming_value'];
        $difference    = round($incomingValue - $outgoingValue, 2);

        if ($difference > 0) {
            $cashDirection = 'paid';
            $cashAmount    = $difference;
        } elseif ($difference < 0) {
            $cashDirection = 'received';
            $cashAmount    = abs($difference);
        } else {
            $cashDirection = 'none';
            $cashAmount    = 0;
        }

        $paymentMethod = $validated['payment_method'] ?? 'cash';

        DB::transaction(function () use (
            $validated, $outgoingGoat, $outgoingValue,
            $incomingValue, $cashDirection, $cashAmount, $paymentMethod
        ) {
            // --- Step 1: Mark outgoing goat as sold ---
            $outgoingGoat->update([
                'status'    => 'sold',
                'sale_price'=> $outgoingValue,
                'sale_date' => $validated['exchange_date'],
            ]);

            // --- Step 2: Income transaction for outgoing goat value ---
            Transaction::create([
                'type'           => 'income',
                'category'       => 'Goat Sales',
                'amount'         => $outgoingValue,
                'date'           => $validated['exchange_date'],
                'goat_id'        => $outgoingGoat->id,
                'payment_method' => $paymentMethod,
                'notes'          => 'Exchanged with ' . ($validated['counterparty'] ?? 'counterparty') . ' for incoming goat',
            ]);

            // --- Step 3: Create the new incoming goat ---
            $incomingGoat = Goat::create([
                'tag_id'          => $validated['incoming_tag_id'],
                'breed_id'        => $validated['breed_id'],
                'gender'          => $validated['incoming_gender'],
                'weight_kg'       => $validated['incoming_weight_kg'] ?? 0,
                'purchase_date'   => $validated['exchange_date'],
                'purchase_weight' => $validated['incoming_weight_kg'] ?? 0,
                'vendor'          => $validated['counterparty'] ?? 'Exchange',
                'status'          => 'grower',
                'purchase_price'  => $incomingValue,
                'estimated_value' => $incomingValue,
                'birth_type'      => 'purchased',
            ]);

            // --- Step 4: Record cash difference ---
            if ($cashDirection === 'paid' && $cashAmount > 0) {
                // Paying extra: it's an acquisition cost (expense)
                Transaction::create([
                    'type'           => 'expense',
                    'category'       => 'Goat Purchases',
                    'amount'         => $cashAmount,
                    'date'           => $validated['exchange_date'],
                    'goat_id'        => $incomingGoat->id,
                    'payment_method' => $paymentMethod,
                    'notes'          => 'Cash difference paid for exchange (received ' . $validated['incoming_tag_id'] . ')',
                ]);
            } elseif ($cashDirection === 'received' && $cashAmount > 0) {
                // Receiving cash back: it's income
                Transaction::create([
                    'type'           => 'income',
                    'category'       => 'Other Income',
                    'amount'         => $cashAmount,
                    'date'           => $validated['exchange_date'],
                    'goat_id'        => $outgoingGoat->id,
                    'payment_method' => $paymentMethod,
                    'notes'          => 'Cash received from exchange (gave ' . $outgoingGoat->tag_id . ')',
                ]);
            }

            // --- Step 5: Create exchange record ---
            Exchange::create([
                'exchange_date'     => $validated['exchange_date'],
                'outgoing_goat_id'  => $outgoingGoat->id,
                'outgoing_value'    => $outgoingValue,
                'incoming_goat_id'  => $incomingGoat->id,
                'incoming_tag_id'   => $validated['incoming_tag_id'],
                'breed_id'          => $validated['breed_id'],
                'incoming_gender'   => $validated['incoming_gender'],
                'incoming_weight_kg'=> $validated['incoming_weight_kg'] ?? 0,
                'incoming_value'    => $incomingValue,
                'counterparty'      => $validated['counterparty'] ?? null,
                'cash_direction'    => $cashDirection,
                'cash_amount'       => $cashAmount,
                'payment_method'    => $paymentMethod,
                'notes'             => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('exchanges.index')
            ->with('success', "Exchange recorded successfully. {$outgoingGoat->tag_id} → {$validated['incoming_tag_id']}.");
    }

    /**
     * Display full exchange details.
     */
    public function show(Exchange $exchange)
    {
        $exchange->load(['outgoingGoat.breed', 'incomingGoat.breed', 'breed']);

        return view('exchanges.show', compact('exchange'));
    }
}
