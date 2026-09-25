<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $sales = Sale::query()
            ->with(['customer:id,name'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('sales/index', [
            'sales' => $sales->through(fn (Sale $s): array => [
                'id' => $s->id,
                'invoice_no' => $s->invoice_no,
                'customer' => $s->customer?->name,
                'status' => $s->status,
                'grand_total' => (float) $s->grand_total,
                'paid_amount' => (float) $s->paid_amount,
                'balance' => (float) $s->grand_total - (float) $s->paid_amount,
                'created_at' => $s->created_at?->toDateTimeString(),
            ]),
            'filters' => $request->only(['status', 'from', 'to']),
        ]);
    }

    public function show(Sale $sale): Response
    {
        $sale->load(['items', 'payments.paymentMethod:id,name', 'customer:id,name,phone']);

        return Inertia::render('sales/show', [
            'sale' => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'status' => $sale->status,
                'subtotal' => (float) $sale->subtotal,
                'tax_amount' => (float) $sale->tax_amount,
                'discount_amount' => (float) $sale->discount_amount,
                'grand_total' => (float) $sale->grand_total,
                'paid_amount' => (float) $sale->paid_amount,
                'customer' => $sale->customer,
                'created_at' => $sale->created_at?->toDateTimeString(),
                'items' => $sale->items->map(fn ($i): array => [
                    'id' => $i->id,
                    'product_id' => $i->product_id,
                    'product_unit_id' => $i->product_unit_id,
                    'quantity' => (float) $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                    'line_total' => (float) $i->line_total,
                ]),
                'payments' => $sale->payments->map(fn ($p): array => [
                    'id' => $p->id,
                    'method' => $p->paymentMethod?->name,
                    'amount' => (float) $p->amount,
                ]),
            ],
        ]);
    }
}
