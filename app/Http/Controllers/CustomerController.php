<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('customers.index', compact('customers'));
    }

    /**
     * Server-side DataTables AJAX endpoint for Customers.
     */
    public function data(Request $request)
    {
        $query = Customer::select('customers.*');

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value'] ?? null) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%")
                          ->orWhere('address', 'like', "%{$search}%");
                    });
                }
            })
            ->editColumn('name', function (Customer $customer) {
                return '<a href="' . route('customers.show', $customer) . '" class="text-indigo-600 font-medium hover:underline">' . e($customer->name) . '</a>';
            })
            ->addColumn('phone', fn (Customer $customer) => e($customer->phone ?? '—'))
            ->addColumn('email', fn (Customer $customer) => e($customer->email ?? '—'))
            ->addColumn('address', fn (Customer $customer) => e(\Illuminate\Support\Str::limit($customer->address, 60) ?: '—'))
            ->addColumn('created_at', fn (Customer $customer) => $customer->created_at?->format('M d, Y'))
            ->addColumn('actions', function (Customer $customer) {
                return '<a href="' . route('customers.edit', $customer) . '" class="text-indigo-600 hover:underline text-xs">Edit</a> '
                    . '<form method="POST" action="' . route('customers.destroy', $customer) . '" class="inline" onsubmit="return confirm(\'Delete this buyer?\')">'
                    . csrf_field() . method_field('DELETE')
                    . '<button class="text-red-600 hover:underline text-xs">Delete</button></form>';
            })
            ->rawColumns(['name', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'email'   => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes'   => ['nullable', 'string'],
        ]);

        Customer::create($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', "Buyer {$validated['name']} created.");
    }

    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'email'   => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes'   => ['nullable', 'string'],
        ]);

        $customer->update($validated);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Buyer {$customer->name} updated.");
    }

    public function destroy(Customer $customer)
    {
        if (class_exists(\App\Models\Sale::class) && $customer->sales()->exists()) {
            return redirect()
                ->route('customers.show', $customer)
                ->with('error', "Buyer {$customer->name} has recorded sales and cannot be deleted.");
        }

        $name = $customer->name;
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', "Buyer {$name} deleted.");
    }
}
