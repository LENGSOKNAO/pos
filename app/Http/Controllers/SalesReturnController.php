<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesReturn;
use App\Models\Stock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SalesReturnController extends Controller
{
    public function index(): Response
    {
        $returns = SalesReturn::query()
            ->with(['sale:id,invoice_no'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $sales = Sale::orderByDesc('id')->limit(50)->get(['id', 'invoice_no']);

        return Inertia::render('sales-returns/index', [
            'returns' => $returns->through(fn (SalesReturn $r): array => [
                'id' => $r->id,
                'return_no' => $r->return_no,
                'sale' => $r->sale?->invoice_no,
                'status' => $r->status,
                'total' => (float) $r->total,
                'created_at' => $r->created_at?->toDateTimeString(),
            ]),
            'sales' => $sales,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        DB::transaction(function () use ($validated): void {
            $sale = Sale::findOrFail($validated['sale_id']);
            $total = 0;

            $nextId = (int) (SalesReturn::max('id') ?? 0) + 1;
            $salesReturn = SalesReturn::create([
                'return_no' => 'SR-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT),
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'status' => 'completed',
                'total' => 0,
            ]);

            foreach ($validated['items'] as $row) {
                /** @var SaleItem $saleItem */
                $saleItem = SaleItem::where('id', $row['sale_item_id'])->where('sale_id', $sale->id)->firstOrFail();

                if ((float) $row['quantity'] > (float) $saleItem->quantity) {
                    throw new \RuntimeException("Return quantity exceeds sold quantity for item {$saleItem->id}.");
                }

                $lineTotal = (float) $row['quantity'] * (float) $saleItem->unit_price;
                $total += $lineTotal;

                $salesReturn->items()->create([
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $row['quantity'],
                    'unit_price' => $saleItem->unit_price,
                    'line_total' => $lineTotal,
                ]);

                if ($saleItem->product_unit_id) {
                    $stock = Stock::where('warehouse_id', $validated['warehouse_id'])
                        ->where('product_unit_id', $saleItem->product_unit_id)
                        ->lockForUpdate()
                        ->first();

                    if ($stock === null) {
                        $stock = Stock::create([
                            'warehouse_id' => $validated['warehouse_id'],
                            'product_unit_id' => $saleItem->product_unit_id,
                            'quantity' => 0,
                        ]);
                    }

                    $stock->increment('quantity', (float) $row['quantity']);
                }
            }

            $salesReturn->update(['total' => $total]);

            if ($total > 0) {
                $salesReturn->refunds()->create([
                    'payment_method_id' => $validated['payment_method_id'] ?? null,
                    'amount' => $total,
                ]);
            }
        });

        return redirect()->route('sales-returns.index');
    }
}
