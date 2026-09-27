<?php

namespace App\Http\Controllers\Api\V1\Sales;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'branch', 'salesOrder', 'items.product', 'payments.paymentMethod']);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($invoices);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
            'payments' => 'sometimes|array',
            'payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference_number' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;
                $subtotal += $itemTotal - $itemDiscount + $itemTax;
            }

            $invoice = Invoice::create([
                'branch_id' => $data['branch_id'],
                'customer_id' => $data['customer_id'],
                'sales_order_id' => $data['sales_order_id'],
                'invoice_number' => 'INV-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'invoice_date' => now(),
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
                'total' => $subtotal,
                'paid_amount' => 0,
                'due_amount' => $subtotal,
                'status' => 'pending',
            ]);

            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;
                $itemNetTotal = $itemTotal - $itemDiscount + $itemTax;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $itemDiscount,
                    'tax' => $itemTax,
                    'cost_price' => Product::find($item['product_id'])->cost_price,
                    'total' => $itemNetTotal,
                ]);
            }

            // Process payments if provided
            if (isset($data['payments'])) {
                $totalPaid = 0;
                foreach ($data['payments'] as $paymentData) {
                    Payment::create([
                        'company_id' => $invoice->branch->company_id,
                        'customer_id' => $invoice->customer_id,
                        'invoice_id' => $invoice->id,
                        'payment_method_id' => $paymentData['payment_method_id'],
                        'amount' => $paymentData['amount'],
                        'reference_number' => $paymentData['reference_number'] ?? null,
                        'status' => 'completed',
                        'received_by' => auth()->id(),
                    ]);
                    $totalPaid += $paymentData['amount'];
                }

                $invoice->update([
                    'paid_amount' => $totalPaid,
                    'due_amount' => $invoice->total - $totalPaid,
                    'status' => $totalPaid >= $invoice->total ? 'paid' : ($totalPaid > 0 ? 'partial_paid' : 'pending'),
                ]);
            }

            return $this->success($invoice->load('items.product', 'payments.paymentMethod'), 'Invoice created successfully', 201);
        });
    }

    public function show(Invoice $invoice)
    {
        return $this->success($invoice->load(['customer', 'branch', 'salesOrder', 'items.product', 'payments.paymentMethod', 'salesReturns', 'customerPayments']));
    }

    public function cancel(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'cancelled') {
            return $this->error('Invoice is already cancelled', 400);
        }

        if ($invoice->status === 'paid' || $invoice->status === 'partial_paid') {
            return $this->error('Cannot cancel paid invoice. Create a refund instead.', 400);
        }

        $invoice->update(['status' => 'cancelled']);

        return $this->success($invoice, 'Invoice cancelled successfully');
    }

    public function print(Invoice $invoice)
    {
        // Return data formatted for printing
        return $this->success($invoice->load(['customer', 'branch', 'items.product', 'payments.paymentMethod']));
    }
}
