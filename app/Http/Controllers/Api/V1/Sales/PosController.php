<?php

namespace App\Http\Controllers\Api\V1\Sales;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\EmployeeCommission;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LoyaltyTransaction;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends BaseApiController
{
    public function products(Request $request)
    {
        $query = Product::with('variants')
            ->where('status', 'active')
            ->where('company_id', auth()->user()->employee?->company_id);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('name')->paginate($request->get('per_page', 20));

        return $this->paginated($products);
    }

    public function customers(Request $request)
    {
        $query = Customer::where('company_id', auth()->user()->employee?->company_id)
            ->where('status', 'active');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->paginate($request->get('per_page', 20));

        return $this->paginated($customers);
    }

    public function checkout(Request $request)
    {
        $data = $this->validateRequest($request, [
            'cash_session_id' => 'required|exists:cash_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference_number' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $cashSession = CashSession::findOrFail($data['cash_session_id']);
        if ($cashSession->status !== 'open') {
            return $this->error('Cash session is not open', 400);
        }

        return DB::transaction(function () use ($data) {
            $employee = auth()->user()->employee;

            // Calculate totals
            $subtotal = 0;
            $totalDiscount = $data['discount'] ?? 0;
            $totalTax = $data['tax'] ?? 0;

            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;
                $subtotal += $itemTotal - $itemDiscount + $itemTax;
            }

            $total = $subtotal - $totalDiscount + $totalTax;

            // Create sales order
            $salesOrder = SalesOrder::create([
                'company_id' => $employee->company_id,
                'branch_id' => $employee->branch_id,
                'customer_id' => $data['customer_id'],
                'employee_id' => $employee->id,
                'order_number' => 'SO-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'order_type' => 'pos',
                'subtotal' => $subtotal,
                'discount' => $totalDiscount,
                'tax' => $totalTax,
                'total' => $total,
                'status' => 'completed',
            ]);

            // Create sales order items and update stock
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $costPrice = $product->cost_price;
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;
                $itemNetTotal = $itemTotal - $itemDiscount + $itemTax;

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $itemDiscount,
                    'tax' => $itemTax,
                    'cost_price' => $costPrice,
                    'total' => $itemNetTotal,
                ]);

                // Update stock
                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $employee->branch?->warehouses()->first()?->id)
                    ->first();

                if ($stock) {
                    $stock->quantity -= $item['quantity'];
                    $stock->save();

                    // Create stock movement
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $stock->warehouse_id,
                        'movement_type' => 'out',
                        'reference_type' => 'sales_order',
                        'reference_id' => $salesOrder->id,
                        'quantity' => -$item['quantity'],
                        'unit_cost' => $costPrice,
                        'balance_after' => $stock->quantity,
                        'employee_id' => $employee->id,
                    ]);
                }
            }

            // Create invoice
            $invoice = Invoice::create([
                'branch_id' => $employee->branch_id,
                'customer_id' => $data['customer_id'],
                'sales_order_id' => $salesOrder->id,
                'invoice_number' => 'INV-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'subtotal' => $subtotal,
                'discount' => $totalDiscount,
                'tax' => $totalTax,
                'total' => $total,
                'paid_amount' => $total,
                'due_amount' => 0,
                'status' => 'paid',
            ]);

            // Create invoice items
            foreach ($salesOrder->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'cost_price' => $item->cost_price,
                    'total' => $item->total,
                ]);
            }

            // Process payments
            $totalPaid = 0;
            foreach ($data['payments'] as $paymentData) {
                $payment = Payment::create([
                    'company_id' => $employee->company_id,
                    'customer_id' => $data['customer_id'],
                    'invoice_id' => $invoice->id,
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'amount' => $paymentData['amount'],
                    'reference_number' => $paymentData['reference_number'] ?? null,
                    'status' => 'completed',
                    'received_by' => auth()->id(),
                ]);

                $totalPaid += $paymentData['amount'];
            }

            // Create commissions if applicable
            if ($employee->commission_rate > 0) {
                EmployeeCommission::create([
                    'employee_id' => $employee->id,
                    'invoice_id' => $invoice->id,
                    'commission_rate' => $employee->commission_rate,
                    'commission_amount' => ($total * $employee->commission_rate) / 100,
                ]);
            }

            // Update customer loyalty points
            if ($data['customer_id']) {
                $customer = Customer::find($data['customer_id']);
                $pointsEarned = floor($total / 10); // 1 point per $10
                if ($pointsEarned > 0) {
                    $customer->increment('loyalty_points', $pointsEarned);
                    LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'points' => $pointsEarned,
                        'transaction_type' => 'earn',
                    ]);
                }
            }

            return $this->success([
                'sales_order' => $salesOrder->load('items.product'),
                'invoice' => $invoice->load('items.product'),
                'payments' => $invoice->payments,
            ], 'Checkout completed successfully');
        });
    }

    public function hold(Request $request)
    {
        $data = $this->validateRequest($request, [
            'cash_session_id' => 'required|exists:cash_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ]);

        $employee = auth()->user()->employee;

        return DB::transaction(function () use ($data, $employee) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;
                $subtotal += $itemTotal - $itemDiscount + $itemTax;
            }

            $salesOrder = SalesOrder::create([
                'company_id' => $employee->company_id,
                'branch_id' => $employee->branch_id,
                'customer_id' => $data['customer_id'],
                'employee_id' => $employee->id,
                'order_number' => 'HOLD-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'order_type' => 'pos',
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
                'total' => $subtotal,
                'status' => 'held',
            ]);

            foreach ($data['items'] as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    'cost_price' => Product::find($item['product_id'])->cost_price,
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $this->success($salesOrder->load('items.product'), 'Order held successfully');
        });
    }

    public function heldOrders(Request $request)
    {
        $employee = auth()->user()->employee;

        $heldOrders = SalesOrder::with('items.product', 'customer')
            ->where('employee_id', $employee->id)
            ->where('status', 'held')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->paginated($heldOrders);
    }

    public function resume(Request $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'held') {
            return $this->error('Order is not held', 400);
        }

        $salesOrder->update(['status' => 'confirmed']);

        return $this->success($salesOrder->load('items.product'), 'Order resumed successfully');
    }
}
