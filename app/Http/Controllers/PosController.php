<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        if ($request->filled('barcode')) {
            $code = trim((string) $request->query('barcode'));

            $productUnit = ProductUnit::query()->where('barcode', $code)->with('product:id,name')->first();

            if ($productUnit === null) {
                $barcode = ProductBarcode::query()->where('barcode', $code)->first();
                if ($barcode !== null && $barcode->product_unit_id !== null) {
                    $productUnit = ProductUnit::with('product:id,name')->find($barcode->product_unit_id);
                } elseif ($barcode !== null) {
                    $productUnit = ProductUnit::with('product:id,name')->where('product_id', $barcode->product_id)->first();
                }
            }

            if ($productUnit === null) {
                return response()->json(['message' => 'Barcode not found.'], 404);
            }

            return response()->json([
                'product_unit_id' => $productUnit->id,
                'product' => ['id' => $productUnit->product->id, 'name' => $productUnit->product->name],
                'unit' => $productUnit->unit?->short_name ?? $productUnit->unit?->name,
                'sale_price' => (float) $productUnit->sale_price,
            ]);
        }

        $search = (string) $request->query('search', '');

        $products = Product::query()
            ->with(['productUnits.unit:id,name,short_name', 'category:id,name', 'brand:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(48)
            ->get();

        $stockByUnit = Stock::query()
            ->selectRaw('product_unit_id, SUM(quantity) as qty')
            ->whereIn('product_unit_id', $products->flatMap->productUnits->pluck('id')->filter()->all() ?: [0])
            ->groupBy('product_unit_id')
            ->pluck('qty', 'product_unit_id');

        $mapped = $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'units' => $product->productUnits->map(fn (ProductUnit $pu): array => [
                'id' => $pu->id,
                'unit' => $pu->unit?->short_name ?? $pu->unit?->name,
                'sale_price' => (float) $pu->sale_price,
                'stock' => (float) ($stockByUnit[$pu->id] ?? 0),
            ])->all(),
            'sale_price' => (float) $product->sale_price,
        ]);

        $receipt = null;
        $receiptId = $request->session()->get('receipt_sale_id');
        if ($receiptId !== null) {
            $sale = Sale::with(['items', 'payments.paymentMethod:id,name', 'customer:id,name,code'])->find($receiptId);
            if ($sale !== null) {
                $receipt = [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'created_at' => $sale->created_at?->toDateTimeString(),
                    'cashier' => $sale->user?->name ?? auth()->user()?->name,
                    'customer' => $sale->customer,
                    'subtotal' => (float) $sale->subtotal,
                    'discount_amount' => (float) $sale->discount_amount,
                    'grand_total' => (float) $sale->grand_total,
                    'paid_amount' => (float) $sale->paid_amount,
                    'change' => (float) $sale->paid_amount - (float) $sale->grand_total,
                    'items' => $sale->items->map(fn ($i): array => [
                        'quantity' => (float) $i->quantity,
                        'unit_price' => (float) $i->unit_price,
                        'line_total' => (float) $i->line_total,
                    ])->all(),
                    'payments' => $sale->payments->map(fn ($p): array => [
                        'payment_method_id' => $p->payment_method_id,
                        'method' => $p->paymentMethod?->name ?? '—',
                        'amount' => (float) $p->amount,
                    ])->all(),
                ];
            }
        }

        return Inertia::render('pos/index', [
            'products' => $mapped,
            'warehouses' => Warehouse::where('is_active', true)->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(['id', 'name', 'code']),
            'customers' => Customer::where('status', 'active')->orderBy('name')->limit(200)->get(['id', 'name', 'code']),
            'filters' => ['search' => $search],
            'receipt' => $receipt,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'payments' => ['nullable', 'array', 'min:1'],
            'payments.*.payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'discount_type' => ['nullable', 'in:fixed,percent'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_unit_id' => ['required', 'exists:product_units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $saleId = null;

        DB::transaction(function () use ($validated, &$saleId): void {
            $lineTotal = collect($validated['items'])->sum(fn (array $item): float => (float) $item['quantity'] * (float) $item['unit_price']);
            $discountType = $validated['discount_type'] ?? 'fixed';
            $discountInput = (float) ($validated['discount_amount'] ?? 0);
            if ($discountType === 'percent') {
                if ($discountInput < 0 || $discountInput > 100) {
                    throw new \RuntimeException('Percent discount must be between 0 and 100.');
                }
                $discount = round($lineTotal * $discountInput / 100, 2);
            } else {
                $discount = min($discountInput, $lineTotal);
            }
            $grandTotal = $lineTotal - $discount;

            $paymentsInput = $validated['payments'] ?? null;
            if ($paymentsInput !== null) {
                $paidSum = round(collect($paymentsInput)->sum(fn (array $p): float => (float) $p['amount']), 2);
                if ($paidSum < $grandTotal - 0.01) {
                    throw new \RuntimeException('Tendered amount is less than the total.');
                }
                $paidAmount = $paidSum;
            } else {
                $paidAmount = (float) ($validated['paid_amount'] ?? $grandTotal);
            }

            $nextId = (int) (Sale::max('id') ?? 0) + 1;
            $invoiceNo = 'INV-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'customer_id' => $validated['customer_id'] ?? null,
                'user_id' => auth()->id(),
                'status' => 'completed',
                'subtotal' => $lineTotal,
                'tax_amount' => 0,
                'discount_amount' => $discount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
            ]);

            $saleId = $sale->id;

            foreach ($validated['items'] as $item) {
                $productUnit = ProductUnit::findOrFail($item['product_unit_id']);

                $sale->items()->create([
                    'product_id' => $productUnit->product_id,
                    'product_unit_id' => $productUnit->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => (float) $item['quantity'] * (float) $item['unit_price'],
                ]);

                $stock = Stock::where('warehouse_id', $validated['warehouse_id'])
                    ->where('product_unit_id', $productUnit->id)
                    ->lockForUpdate()
                    ->first();

                if ($stock === null || (float) $stock->quantity < (float) $item['quantity']) {
                    throw new \RuntimeException("Insufficient stock for product unit {$productUnit->id}.");
                }

                $stock->decrement('quantity', (float) $item['quantity']);
            }

            $paymentRows = $paymentsInput ?? [[
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'amount' => $validated['paid_amount'] ?? $lineTotal,
            ]];
            foreach ($paymentRows as $row) {
                $sale->payments()->create([
                    'payment_method_id' => $row['payment_method_id'] ?? null,
                    'amount' => (float) ($row['amount'] ?? 0),
                ]);
            }
        });

        return redirect()->route('pos.index')->with('receipt_sale_id', $saleId);
    }
}
