<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use App\Models\Exchange;
use App\Models\Goat;
use App\Models\Investment;
use App\Models\Investor;
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
        $query = Exchange::with(['outgoingGoat.breed', 'incomingGoat.breed', 'breed', 'investor']);

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
        $query = Exchange::with(['outgoingGoat.breed', 'incomingGoat.breed', 'breed', 'investor'])
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
            ->addColumn('investor', fn (Exchange $e) => $e->investor ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">' . e($e->investor->name) . '</span>' : '<span class="text-gray-400 text-xs">Farm</span>')
            ->addColumn('actions', function (Exchange $e) {
                return '<a href="' . route('exchanges.show', $e) . '" class="text-indigo-600 hover:underline text-xs">View</a>'
                    . ' <a href="' . route('exchanges.edit', $e) . '" class="text-indigo-600 hover:underline text-xs ml-2">Edit</a>';
            })
            ->rawColumns(['outgoing', 'incoming', 'cash', 'investor', 'actions'])
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
        $investors = Investor::orderBy('name')->pluck('name', 'id');

        return view('exchanges.create', compact('herdGoats', 'breeds', 'investors'));
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
            'investor_id'       => ['nullable', 'exists:investors,id'],
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
            $investorId = $validated['investor_id'] ?? null;

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
                'investor_id'    => $investorId,
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
                // If an investor paid, tag it so it counts toward their
                // expense_paid and settlement balance.
                Transaction::create([
                    'type'           => 'expense',
                    'category'       => 'Goat Purchases',
                    'amount'         => $cashAmount,
                    'date'           => $validated['exchange_date'],
                    'goat_id'        => $incomingGoat->id,
                    'investor_id'    => $investorId,
                    'payment_method' => $paymentMethod,
                    'notes'          => 'Cash difference paid for exchange (received ' . $validated['incoming_tag_id'] . ')',
                ]);

                // --- Step 4b: If an investor funded the cash difference,
                // record it as a capital investment so their total_capital
                // and share_pct increase to reflect the money they put in. ---
                if ($investorId) {
                    Investment::create([
                        'investor_id' => $investorId,
                        'amount'      => $cashAmount,
                        'date'        => $validated['exchange_date'],
                        'notes'       => 'Cash difference for exchange: gave ' . $outgoingGoat->tag_id . ', received ' . $validated['incoming_tag_id'],
                    ]);
                }
            } elseif ($cashDirection === 'received' && $cashAmount > 0) {
                // Receiving cash back: it's income
                // If an investor received it, tag it accordingly.
                Transaction::create([
                    'type'           => 'income',
                    'category'       => 'Other Income',
                    'amount'         => $cashAmount,
                    'date'           => $validated['exchange_date'],
                    'goat_id'        => $outgoingGoat->id,
                    'investor_id'    => $investorId,
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
                'investor_id'       => $investorId,
                'notes'             => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('exchanges.index')
            ->with('success', "Exchange recorded successfully. {$outgoingGoat->tag_id} → {$validated['incoming_tag_id']}.");
    }

    /**
     * Show the exchange edit form.
     */
    public function edit(Exchange $exchange)
    {
        $exchange->load(['outgoingGoat.breed', 'incomingGoat.breed', 'breed', 'investor']);

        // Include the outgoing goat even though it's sold now
        $herdGoats = Goat::whereNotIn('status', ['sold', 'deceased'])
            ->orWhere('id', $exchange->outgoing_goat_id)
            ->orderBy('tag_id')
            ->get(['id', 'tag_id', 'name', 'breed_id', 'gender', 'weight_kg', 'purchase_price', 'estimated_value']);
        $herdGoats->load('breed');

        $breeds = Breed::orderBy('name')->pluck('name', 'id');
        $investors = Investor::orderBy('name')->pluck('name', 'id');

        return view('exchanges.edit', compact('exchange', 'herdGoats', 'breeds', 'investors'));
    }

    /**
     * Update an exchange and synchronise all linked records.
     *
     * The exchange created up to 3 transactions + modified 2 goats.
     * On edit we update the exchange fields, update both goats' values,
     * and upsert the matching transactions by matching on goat_id +
     * category + date proximity.
     */
    public function update(Request $request, Exchange $exchange)
    {
        $validated = $request->validate([
            'exchange_date'     => ['required', 'date', 'before_or_equal:today'],
            'outgoing_value'    => ['required', 'numeric', 'min:0'],
            'breed_id'          => ['required', 'exists:breeds,id'],
            'incoming_gender'   => ['required', 'in:male,female'],
            'incoming_weight_kg'=> ['nullable', 'numeric', 'min:0'],
            'incoming_value'    => ['required', 'numeric', 'min:0'],
            'counterparty'      => ['nullable', 'string', 'max:255'],
            'payment_method'    => ['nullable', 'in:cash,bank_transfer,check,other'],
            'investor_id'       => ['nullable', 'exists:investors,id'],
            'notes'             => ['nullable', 'string'],
        ]);

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
        $investorId    = $validated['investor_id'] ?? null;

        DB::transaction(function () use (
            $exchange, $validated, $outgoingValue, $incomingValue,
            $cashDirection, $cashAmount, $paymentMethod, $investorId
        ) {
            $outgoingGoat = $exchange->outgoingGoat;
            $incomingGoat = $exchange->incomingGoat;

            // --- Update outgoing goat sale info ---
            if ($outgoingGoat) {
                $outgoingGoat->update([
                    'sale_price' => $outgoingValue,
                    'sale_date'  => $validated['exchange_date'],
                ]);
            }

            // --- Update incoming goat ---
            if ($incomingGoat) {
                $incomingGoat->update([
                    'breed_id'        => $validated['breed_id'],
                    'gender'          => $validated['incoming_gender'],
                    'weight_kg'       => $validated['incoming_weight_kg'] ?? 0,
                    'purchase_date'   => $validated['exchange_date'],
                    'purchase_weight' => $validated['incoming_weight_kg'] ?? 0,
                    'vendor'          => $validated['counterparty'] ?? 'Exchange',
                    'purchase_price'  => $incomingValue,
                    'estimated_value' => $incomingValue,
                ]);
            }

            // --- Sync the 3 auto-generated transactions ---
            // Find them by matching the original exchange date + goat + category pattern.
            $oldDate      = $exchange->exchange_date->format('Y-m-d');
            $oldOutgoing  = $exchange->outgoing_goat_id;
            $oldIncoming  = $exchange->incoming_goat_id;

            // 1. Goat Sales income (outgoing goat)
            $this->upsertExchangeTransaction(
                type: 'income',
                category: 'Goat Sales',
                goatId: $oldOutgoing,
                oldDate: $oldDate,
                amount: $outgoingValue,
                newDate: $validated['exchange_date'],
                investorId: $investorId,
                paymentMethod: $paymentMethod,
                notes: 'Exchanged with ' . ($validated['counterparty'] ?? 'counterparty') . ' for incoming goat',
            );

            // 2. Cash difference transaction (may switch between expense / income / none)
            // First, delete any existing cash-diff transaction from the old exchange
            Transaction::where('goat_id', $oldIncoming)
                ->where('category', 'Goat Purchases')
                ->where('date', $oldDate)
                ->where('notes', 'like', '%cash difference%')
                ->delete();
            Transaction::where('goat_id', $oldOutgoing)
                ->where('category', 'Other Income')
                ->where('date', $oldDate)
                ->where('notes', 'like', '%cash received from exchange%')
                ->delete();

            // Then create the new one if needed
            if ($cashDirection === 'paid' && $cashAmount > 0) {
                Transaction::create([
                    'type'           => 'expense',
                    'category'       => 'Goat Purchases',
                    'amount'         => $cashAmount,
                    'date'           => $validated['exchange_date'],
                    'goat_id'        => $oldIncoming,
                    'investor_id'    => $investorId,
                    'payment_method' => $paymentMethod,
                    'notes'          => 'Cash difference paid for exchange (received ' . $exchange->incoming_tag_id . ')',
                ]);
            } elseif ($cashDirection === 'received' && $cashAmount > 0) {
                Transaction::create([
                    'type'           => 'income',
                    'category'       => 'Other Income',
                    'amount'         => $cashAmount,
                    'date'           => $validated['exchange_date'],
                    'goat_id'        => $oldOutgoing,
                    'investor_id'    => $investorId,
                    'payment_method' => $paymentMethod,
                    'notes'          => 'Cash received from exchange (gave ' . ($outgoingGoat?->tag_id ?? '') . ')',
                ]);
            }

            // --- Sync Investment record for the cash difference ---
            // Delete the old investment linked to this exchange
            Investment::where('investor_id', $exchange->investor_id)
                ->where('date', $oldDate)
                ->where('notes', 'like', '%Cash difference for exchange%')
                ->delete();

            // If an investor is now assigned and they're paying cash, create/update investment
            if ($investorId && $cashDirection === 'paid' && $cashAmount > 0) {
                Investment::create([
                    'investor_id' => $investorId,
                    'amount'      => $cashAmount,
                    'date'        => $validated['exchange_date'],
                    'notes'       => 'Cash difference for exchange: gave ' . ($outgoingGoat?->tag_id ?? '') . ', received ' . $exchange->incoming_tag_id,
                ]);
            }

            // --- Update the exchange record itself ---
            $exchange->update([
                'exchange_date'     => $validated['exchange_date'],
                'outgoing_value'    => $outgoingValue,
                'breed_id'          => $validated['breed_id'],
                'incoming_gender'   => $validated['incoming_gender'],
                'incoming_weight_kg'=> $validated['incoming_weight_kg'] ?? 0,
                'incoming_value'    => $incomingValue,
                'counterparty'      => $validated['counterparty'] ?? null,
                'cash_direction'    => $cashDirection,
                'cash_amount'       => $cashAmount,
                'payment_method'    => $paymentMethod,
                'investor_id'       => $investorId,
                'notes'             => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('exchanges.show', $exchange)
            ->with('success', 'Exchange updated successfully.');
    }

    /**
     * Find an existing exchange transaction and update it in place.
     * Used during edit to keep the original transactions in sync.
     */
    private function upsertExchangeTransaction(
        string $type,
        string $category,
        ?int $goatId,
        string $oldDate,
        float $amount,
        string $newDate,
        ?int $investorId,
        string $paymentMethod,
        string $notes,
    ): void {
        $txn = Transaction::where('type', $type)
            ->where('category', $category)
            ->where('goat_id', $goatId)
            ->where('date', $oldDate)
            ->first();

        if ($txn) {
            $txn->update([
                'amount'         => $amount,
                'date'           => $newDate,
                'investor_id'    => $investorId,
                'payment_method' => $paymentMethod,
                'notes'          => $notes,
            ]);
        }
    }

    /**
     * Display full exchange details.
     */
    public function show(Exchange $exchange)
    {
        $exchange->load(['outgoingGoat.breed', 'incomingGoat.breed', 'breed', 'investor']);

        return view('exchanges.show', compact('exchange'));
    }
}
