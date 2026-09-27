<?php

namespace App\Http\Controllers\Api\V1\Sales;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = SalesReturn::with(['invoice', 'customer', 'branch', 'creator', 'approver', 'items.product']);

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $returns = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($returns);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'invoice_id' => 'required|exists:invoices,id',
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'reason' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $invoice = Invoice::findOrFail($data['invoice_id']);

            // Verify items belong to the invoice
            $invoiceProductIds = $invoice->items->pluck('product_id')->toArray();
            foreach ($data['items'] as $item) {
                if (! in_array($item['product_id'], $invoiceProductIds)) {
                    return $this->error('Product not found in invoice', 400);
                }
            }

            $refundAmount = 0;
            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $refundAmount += $itemTotal;
            }

            $salesReturn = SalesReturn::create([
                'invoice_id' => $data['invoice_id'],
                'customer_id' => $data['customer_id'],
                'branch_id' => $data['branch_id'],
                'return_number' => 'RET-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'reason' => $data['reason'],
                'refund_amount' => $refundAmount,
                'status' => 'pending_approval',
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $refundAmount = $item['quantity'] * $item['unit_price'];

                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'refund_amount' => $refundAmount,
                ]);
            }

            return $this->success($salesReturn->load('items.product'), 'Sales return created successfully', 201);
        });
    }

    public function show(SalesReturn $salesReturn)
    {
        return $this->success($salesReturn->load(['invoice', 'customer', 'branch', 'creator', 'approver', 'items.product', 'refunds']));
    }

    public function approve(Request $request, SalesReturn $salesReturn)
    {
        if ($salesReturn->status !== 'pending_approval') {
            return $this->error('Return is not pending approval', 400);
        }

        return DB::transaction(function () use ($salesReturn) {
            foreach ($salesReturn->items as $item) {
                // Restock the product
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $salesReturn->branch->warehouses()->first()?->id)
                    ->first();

                if ($stock) {
                    $stock->quantity += $item->quantity;
                    $stock->save();

                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $stock->warehouse_id,
                        'movement_type' => 'return',
                        'reference_type' => 'sales_return',
                        'reference_id' => $salesReturn->id,
                        'quantity' => $item->quantity,
                        'unit_cost' => $item->unit_price,
                        'balance_after' => $stock->quantity,
                        'employee_id' => auth()->user()?->employee_id,
                    ]);
                }
            }

            $salesReturn->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            // Update invoice status if all items returned
            $invoice = $salesReturn->invoice;
            $totalReturnedQty = $salesReturn->items->sum('quantity');
            $totalInvoiceQty = $invoice->items->sum('quantity');

            if ($totalReturnedQty >= $totalInvoiceQty) {
                $invoice->update(['status' => 'refunded']);
            }

            return $this->success($salesReturn->load('items.product'), 'Sales return approved successfully');
        });
    }

    public function reject(Request $request, SalesReturn $salesReturn)
    {
        if ($salesReturn->status !== 'pending_approval') {
            return $this->error('Return is not pending approval', 400);
        }

        $salesReturn->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return $this->success($salesReturn, 'Sales return rejected successfully');
    }

    public function destroy(SalesReturn $salesReturn)
    {
        if ($salesReturn->status !== 'draft' && $salesReturn->status !== 'pending_approval') {
            return $this->error('Cannot delete return in current status', 400);
        }

        $salesReturn->delete();

        return $this->success(null, 'Sales return deleted successfully');
    }
}
