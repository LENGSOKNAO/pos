<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = PurchaseReturn::with('supplier', 'warehouse', 'creator', 'items.product');

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
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
                    ->orWhereHas('supplier', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $returns = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($returns);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'reason' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $total = 0;
            foreach ($data['items'] as $item) {
                $total += $item['quantity'] * $item['unit_cost'];
            }

            $purchaseReturn = PurchaseReturn::create([
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'return_number' => 'PRT-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'reason' => $data['reason'],
                'total' => $total,
                'status' => 'pending_approval',
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_cost'];

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }

            return $this->success($purchaseReturn->load('items.product'), 'Purchase return created successfully', 201);
        });
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        return $this->success($purchaseReturn->load(['supplier', 'warehouse', 'creator', 'approver', 'items.product']));
    }

    public function approve(Request $request, PurchaseReturn $purchaseReturn)
    {
        if ($purchaseReturn->status !== 'pending_approval') {
            return $this->error('Return is not pending approval', 400);
        }

        return DB::transaction(function () use ($purchaseReturn) {
            foreach ($purchaseReturn->items as $item) {
                // Deduct from stock
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $purchaseReturn->warehouse_id)
                    ->first();

                if ($stock) {
                    $stock->quantity -= $item->quantity;
                    $stock->save();

                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $purchaseReturn->warehouse_id,
                        'movement_type' => 'return',
                        'reference_type' => 'purchase_return',
                        'reference_id' => $purchaseReturn->id,
                        'quantity' => -$item->quantity,
                        'unit_cost' => $item->unit_cost,
                        'balance_after' => $stock->quantity,
                        'employee_id' => auth()->user()?->employee_id,
                    ]);
                }
            }

            $purchaseReturn->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            return $this->success($purchaseReturn->load('items.product'), 'Purchase return approved successfully');
        });
    }

    public function reject(Request $request, PurchaseReturn $purchaseReturn)
    {
        if ($purchaseReturn->status !== 'pending_approval') {
            return $this->error('Return is not pending approval', 400);
        }

        $purchaseReturn->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return $this->success($purchaseReturn, 'Purchase return rejected successfully');
    }
}
