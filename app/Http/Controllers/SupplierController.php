<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');

        $suppliers = Supplier::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('suppliers/index', [
            'suppliers' => $suppliers->through(fn (Supplier $s): array => [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'phone' => $s->phone,
                'balance' => (float) $s->balance,
                'status' => $s->status,
            ]),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $nextId = (int) (Supplier::max('id') ?? 0) + 1;

        Supplier::create([
            'name' => $validated['name'],
            'code' => 'SUP-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT),
            'phone' => $validated['phone'] ?? null,
            'balance' => 0,
            'status' => 'active',
        ]);

        return redirect()->route('suppliers.index');
    }
}
