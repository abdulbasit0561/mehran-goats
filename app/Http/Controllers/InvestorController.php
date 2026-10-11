<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class InvestorController extends Controller
{
    public function index(Request $request)
    {
        $query = Investor::with('investments');

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $investors = $query->latest()->paginate(15)->withQueryString();

        // Total capital across the farm for share % computation
        $totalCapital = (float) \DB::table('investments')->sum('amount');
        $totalExpenseSplit = (float) Investor::sum('expense_split_pct');

        // All investors for the quick-add investment dropdown
        $allInvestors = Investor::orderBy('name')->pluck('name', 'id');

        return view('investors.index', compact('investors', 'totalCapital', 'totalExpenseSplit', 'allInvestors'));
    }

    /**
     * Server-side DataTables AJAX endpoint for Investors.
     */
    public function data(Request $request)
    {
        $totalCapital = (float) \DB::table('investments')->sum('amount');

        $query = Investor::with('investments')->select('investors.*');

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value'] ?? null) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                    });
                }
            })
            ->editColumn('name', function (Investor $investor) {
                return '<a href="' . route('investors.show', $investor) . '" class="text-indigo-600 font-medium hover:underline">' . e($investor->name) . '</a>';
            })
            ->addColumn('role', function (Investor $investor) {
                if ($investor->isManager()) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Manager</span>';
                }
                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Investor</span>';
            })
            ->addColumn('total_capital', fn (Investor $investor) => number_format((float) $investor->total_capital, 0))
            ->addColumn('share_pct', fn (Investor $investor) => number_format($investor->share_pct, 1) . '%')
            ->addColumn('herd_share', fn (Investor $investor) => number_format($investor->herd_share, 0))
            ->addColumn('profit_share', fn (Investor $investor) => number_format($investor->profit_share, 0))
            ->editColumn('expense_split_pct', fn (Investor $investor) => number_format((float) $investor->expense_split_pct, 1) . '%')
            ->addColumn('expense_owed', fn (Investor $investor) => number_format($investor->expense_owed, 0))
            ->addColumn('expense_paid', fn (Investor $investor) => number_format($investor->expense_paid, 0))
            ->addColumn('settlement_balance', function (Investor $investor) {
                $cls = $investor->settlement_balance >= 0 ? 'text-green-600' : 'text-red-600';
                $sign = $investor->settlement_balance >= 0 ? '+' : '';
                return '<span class="font-semibold ' . $cls . '">' . $sign . number_format($investor->settlement_balance, 0) . '</span>';
            })
            ->addColumn('actions', function (Investor $investor) {
                return '<button type="button" onclick="window.dispatchEvent(new CustomEvent(\'open-investment-modal\', { detail: { investorId: ' . $investor->id . ' } }))" class="text-emerald-600 hover:underline text-xs">Investment</button> '
                    . '<a href="' . route('investors.edit', $investor) . '" class="text-indigo-600 hover:underline text-xs">Edit</a> '
                    . '<form method="POST" action="' . route('investors.destroy', $investor) . '" class="inline" data-confirm="Delete this investor?">'
                    . csrf_field() . method_field('DELETE')
                    . '<button class="text-red-600 hover:underline text-xs">Delete</button></form>';
            })
            ->rawColumns(['name', 'role', 'settlement_balance', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('investors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'role'             => ['required', 'in:investor,manager'],
            'expense_split_pct'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'email'            => ['nullable', 'email', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'address'          => ['nullable', 'string', 'max:500'],
            'notes'            => ['nullable', 'string'],
        ]);

        Investor::create($validated);

        return redirect()
            ->route('investors.index')
            ->with('success', "Investor {$validated['name']} created.");
    }

    public function show(Investor $investor)
    {
        $investor->load(['investments' => fn ($q) => $q->latest('date'),
                         'ownershipUnits.goat',
                         'transactions' => fn ($q) => $q->latest('date')->limit(20),
                         'profitDistributions']);

        return view('investors.show', compact('investor'));
    }

    public function edit(Investor $investor)
    {
        return view('investors.edit', compact('investor'));
    }

    public function update(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'role'             => ['required', 'in:investor,manager'],
            'expense_split_pct'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'email'            => ['nullable', 'email', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'address'          => ['nullable', 'string', 'max:500'],
            'notes'            => ['nullable', 'string'],
        ]);

        $investor->update($validated);

        return redirect()
            ->route('investors.show', $investor)
            ->with('success', "Investor {$investor->name} updated.");
    }

    public function destroy(Investor $investor)
    {
        $name = $investor->name;
        $investor->delete();

        return redirect()
            ->route('investors.index')
            ->with('success', "Investor {$name} deleted.");
    }
}
