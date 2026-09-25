<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');

        $orders = PurchaseOrder::query()
            ->with(['supplier:id,name'])
            ->when($search !== '', fn ($q) => $q->where('po_no', 'like', "%{$search}%")->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%")))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('purchases/index', [
            'orders' => $orders->through(fn (PurchaseOrder $o): array => [
                'id' => $o->id,
                'po_no' => $o->po_no,
                'supplier' => $o->supplier?->name,
                'grand_total' => (float) $o->grand_total,
                'status' => $o->status,
                'order_date' => $o->order_date,
                'created_at' => $o->created_at?->toDateTimeString(),
            ]),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        $products = Product::where('is_active', true)->with(['productUnits:id,product_id,unit_id,sale_price'])->orderBy('name')->limit(200)->get(['id', 'name', 'sku']);

        return Inertia::render('purchases/create', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => $products->map(fn (Product $p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'units' => $p->productUnits->map(fn (ProductUnit $pu): array => ['id' => $pu->id])->all(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_unit_id' => ['required', 'exists:product_units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated): void {
            $lineTotal = collect($validated['items'])->sum(fn (array $i): float => (float) $i['quantity'] * (float) $i['unit_cost']);

            $nextId = (int) (PurchaseOrder::max('id') ?? 0) + 1;
            $poNo = 'PO-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

            $order = PurchaseOrder::create([
                'po_no' => $poNo,
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'order_date' => now()->toDateString(),
                'status' => 'received',
                'subtotal' => $lineTotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'grand_total' => $lineTotal,
                'paid_amount' => 0,
            ]);

            foreach ($validated['items'] as $item) {
                $pu = ProductUnit::findOrFail($item['product_unit_id']);

                $order->items()->create([
                    'product_id' => $pu->product_id,
                    'product_unit_id' => $pu->id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => (float) $item['quantity'] * (float) $item['unit_cost'],
                ]);

                $stock = Stock::where('warehouse_id', $validated['warehouse_id'])
                    ->where('product_unit_id', $pu->id)
                    ->lockForUpdate()
                    ->first();

                if ($stock === null) {
                    Stock::create([
                        'warehouse_id' => $validated['warehouse_id'],
                        'product_unit_id' => $pu->id,
                        'quantity' => $item['quantity'],
                    ]);
                } else {
                    $stock->increment('quantity', (float) $item['quantity']);
                }
            }
        });

        return redirect()->route('purchases.index');
    }
}
