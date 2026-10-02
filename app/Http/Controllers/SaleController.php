<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Goat;
use App\Models\Investor;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['goat', 'customer', 'seller']);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('goat', fn ($g) => $g->where('tag_id', 'like', "%{$search}%"))
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('seller', fn ($i) => $i->where('name', 'like', "%{$search}%"));
            });
        }

        $sales = $query->latest('sale_date')->paginate(15)->withQueryString();

        $summary = [
            'total_sales'   => Sale::count(),
            'total_revenue' => (float) Sale::sum('sale_price'),
        ];
        // Per-sale balances floored at 0 so one sale's overpayment cannot
        // cancel another sale's outstanding debt.
        $summary['outstanding'] = (float) Sale::with('payments')->get()
            ->sum(fn (Sale $sale) => $sale->balance_due);

        return view('sales.index', compact('sales', 'summary'));
    }

    /**
     * Server-side DataTables AJAX endpoint for Sales.
     */
    public function data(Request $request)
    {
        $query = Sale::with(['goat', 'customer', 'seller'])->select('sales.*');

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value'] ?? null) {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('goat', fn ($g) => $g->where('tag_id', 'like', "%{$search}%"))
                          ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                          ->orWhereHas('seller', fn ($i) => $i->where('name', 'like', "%{$search}%"));
                    });
                }
            })
            ->editColumn('goat', fn (Sale $sale) => '<a href="' . route('goats.show', $sale->goat_id) . '" class="text-indigo-600 font-medium hover:underline">' . e($sale->goat?->tag_id ?? '—') . '</a>')
            ->addColumn('buyer', fn (Sale $sale) => $sale->customer
                ? '<a href="' . route('customers.show', $sale->customer) . '" class="hover:underline">' . e($sale->customer->name) . '</a>'
                : '<span class="text-gray-400">—</span>')
            ->addColumn('seller', fn (Sale $sale) => $sale->seller ? e($sale->seller->name) : '<span class="text-gray-400">—</span>')
            ->editColumn('sale_date', fn (Sale $sale) => $sale->sale_date->format('M d, Y'))
            ->editColumn('sale_price', fn (Sale $sale) => number_format((float) $sale->sale_price, 0))
            ->editColumn('payment_method', fn (Sale $sale) => ucfirst(str_replace('_', ' ', $sale->payment_method)))
            ->addColumn('status', function (Sale $sale) {
                $badge = match ($sale->status) {
                    'paid'    => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>',
                    'partial' => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Partial</span>',
                    default   => '<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unpaid</span>',
                };
                $balance = $sale->balance_due > 0
                    ? ' <span class="text-xs text-gray-500">(' . number_format($sale->balance_due, 0) . ' due)</span>'
                    : '';
                return $badge . $balance;
            })
            ->addColumn('actions', function (Sale $sale) {
                return '<a href="' . route('sales.show', $sale) . '" class="text-gray-700 hover:underline text-xs">View</a> '
                    . '<a href="' . route('sales.edit', $sale) . '" class="text-indigo-600 hover:underline text-xs">Edit</a> '
                    . '<form method="POST" action="' . route('sales.destroy', $sale) . '" class="inline" onsubmit="return confirm(\'Delete this sale? The goat returns to the herd and the income entry is removed.\')">'
                    . csrf_field() . method_field('DELETE')
                    . '<button class="text-red-600 hover:underline text-xs">Delete</button></form>';
            })
            ->rawColumns(['goat', 'buyer', 'seller', 'status', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $goats     = Goat::whereNotIn('status', ['sold', 'deceased'])->orderBy('tag_id')->get();
        $customers = Customer::orderBy('name')->get();
        $investors = Investor::orderBy('name')->get();

        return view('sales.create', compact('goats', 'customers', 'investors'));
    }

    public function store(Request $request, SaleService $service)
    {
        $validated = $this->validateSale($request);

        $goat = Goat::findOrFail($validated['goat_id']);
        if ($goat->sale()->exists()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', "Goat {$goat->tag_id} has already been sold.");
        }
        if (in_array($goat->status, ['sold', 'deceased'])) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', "Goat {$goat->tag_id} is already sold or deceased and cannot be sold.");
        }

        $validated['photo_path'] = $this->storePhoto($request);

        $sale = $service->recordSale($goat, $validated);

        // recordSale() created a default "paid in full" payment. If the buyer
        // paid less up front, replace it with the actual initial payment.
        $initialRaw = $request->input('initial_paid_amount');
        $initialPaid = ($initialRaw === null || $initialRaw === '')
            ? (float) $validated['sale_price']
            : (float) $initialRaw;

        if ($initialPaid < (float) $validated['sale_price']) {
            $sale->payments()->delete();
            if ($initialPaid > 0) {
                $sale->payments()->create([
                    'amount'         => $initialPaid,
                    'payment_date'   => $validated['sale_date'],
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'notes'          => 'Payment at sale',
                ]);
            }
        }
        $sale->refreshPaymentStatus();

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', "Sale of goat {$goat->tag_id} recorded.");
    }

    public function show(Sale $sale)
    {
        $sale->load(['goat.breed', 'customer', 'seller']);

        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        $sale->load('goat');
        $customers = Customer::orderBy('name')->get();
        $investors = Investor::orderBy('name')->get();

        return view('sales.edit', compact('sale', 'customers', 'investors'));
    }

    public function update(Request $request, Sale $sale, SaleService $service)
    {
        $validated = $this->validateSale($request, editing: true);
        $validated['goat_id'] = $sale->goat_id;

        $validated['photo_path'] = $this->storePhoto($request);

        $service->recordSale($sale->goat, $validated);

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Sale updated.');
    }

    /**
     * Delete a sale: income entry removed, goat returned to the herd
     * (status back to what makes sense: grower) and sale soft-deleted.
     */
    public function destroy(Sale $sale)
    {
        $tagId = $sale->goat?->tag_id ?? '';

        SaleService::undoSale($sale);

        return redirect()
            ->route('sales.index')
            ->with('success', "Sale of goat {$tagId} deleted. Goat returned to the herd.");
    }

    private function validateSale(Request $request, bool $editing = false): array
    {
        return $request->validate([
            // The edit form does not submit goat_id — the sale's goat is fixed.
            'goat_id'            => [$editing ? 'nullable' : 'required', 'integer', 'exists:goats,id'],
            'customer_id'        => ['nullable', 'exists:customers,id'],
            'seller_investor_id' => ['nullable', 'exists:investors,id'],
            'sale_date'          => ['required', 'date'],
            'sale_price'         => ['required', 'numeric', 'min:0.01'],
            'sale_weight'        => ['nullable', 'numeric', 'min:0'],
            'payment_method'     => ['nullable', 'in:cash,bank_transfer,check,other'],
            'photo'              => ['nullable', 'image', 'max:4096'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function storePhoto(Request $request): ?string
    {
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            return $request->file('photo')->store('sales', 'public');
        }

        return null;
    }

    /**
     * Record an installment payment against a sale.
     * Overpayment is allowed but flagged back to the user.
     */
    public function recordPayment(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,check,other'],
            'reference'      => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);

        $sale->payments()->create($validated);
        $sale->refreshPaymentStatus();

        $overpaid = (float) $sale->paid_amount > (float) $sale->sale_price + 0.001;

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', $overpaid
                ? "Payment recorded — note: total paid ({$sale->paid_amount}) now exceeds the sale price."
                : 'Payment recorded.');
    }

    /**
     * Delete a payment; balance and status are recomputed.
     */
    public function deletePayment(Sale $sale, SalePayment $payment)
    {
        if ($payment->sale_id !== $sale->id) {
            abort(404);
        }

        $payment->delete();
        $sale->refreshPaymentStatus();

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Payment deleted; balance recalculated.');
    }
}

