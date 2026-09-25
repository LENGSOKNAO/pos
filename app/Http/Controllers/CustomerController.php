<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');

        $customers = Customer::query()
            ->with('group:id,name')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
            ->when($request->query('group'), fn ($q, $g) => $q->where('customer_group_id', $g))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('customers/index', [
            'customers' => $customers->through(fn (Customer $c): array => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'phone' => $c->phone,
                'group' => $c->group?->name,
                'balance' => (float) $c->balance,
                'status' => $c->status,
            ]),
            'groups' => CustomerGroup::all(['id', 'name']),
            'filters' => ['search' => $search, 'group' => $request->query('group')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'],
        ]);

        $nextId = (int) (Customer::max('id') ?? 0) + 1;

        Customer::create([
            'name' => $validated['name'],
            'code' => 'CUS-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT),
            'phone' => $validated['phone'] ?? null,
            'customer_group_id' => $validated['customer_group_id'] ?? null,
            'balance' => 0,
            'status' => 'active',
        ]);

        return redirect()->route('customers.index');
    }
}
