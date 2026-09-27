<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'branch', 'warehouse', 'creator', 'approver', 'items.product']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($orders);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_date' => 'nullable|date|after_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_cost'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;
                $subtotal += $itemTotal - $itemDiscount + $itemTax;
            }

            $purchaseOrder = PurchaseOrder::create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'warehouse_id' => $data['warehouse_id'],
                'supplier_id' => $data['supplier_id'],
                'order_number' => 'PO-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'order_date' => now(),
                'expected_date' => $data['expected_date'],
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
                'total' => $subtotal,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_cost'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemTax = $item['tax'] ?? 0;
                $itemNetTotal = $itemTotal - $itemDiscount + $itemTax;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'discount' => $itemDiscount,
                    'tax' => $itemTax,
                    'total' => $itemNetTotal,
                ]);
            }

            return $this->success($purchaseOrder->load('items.product'), 'Purchase order created successfully', 201);
        });
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return $this->success($purchaseOrder->load(['supplier', 'branch', 'warehouse', 'creator', 'approver', 'items.product', 'receipts']));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return $this->error('Cannot update order in current status', 400);
        }

        $data = $this->validateRequest($request, [
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'expected_date' => 'nullable|date|after_or_equal:today',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $purchaseOrder) {
            $purchaseOrder->update($data);

            if (isset($data['items'])) {
                $purchaseOrder->items()->delete();

                foreach ($data['items'] as $item) {
                    $itemTotal = $item['quantity'] * $item['unit_cost'];
                    $itemDiscount = $item['discount'] ?? 0;
                    $itemTax = $item['tax'] ?? 0;
                    $itemNetTotal = $itemTotal - $itemDiscount + $itemTax;

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'discount' => $itemDiscount,
                        'tax' => $itemTax,
                        'total' => $itemNetTotal,
                    ]);
                }
            }

            return $this->success($purchaseOrder->load('items.product'), 'Purchase order updated successfully');
        });
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft' && $purchaseOrder->status !== 'pending_approval') {
            return $this->error('Purchase order cannot be approved in current status', 400);
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return $this->success($purchaseOrder->load('items.product'), 'Purchase order approved successfully');
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received' || $purchaseOrder->status === 'cancelled') {
            return $this->error('Cannot cancel order in current status', 400);
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return $this->success($purchaseOrder, 'Purchase order cancelled successfully');
    }
}
