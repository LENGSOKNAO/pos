<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = StockAdjustment::with('warehouse', 'creator', 'approver', 'items.product');

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $adjustments = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($adjustments);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'warehouse_id' => 'required|exists:warehouses,id',
            'adjustment_number' => 'required|string|unique:stock_adjustments,adjustment_number|max:50',
            'reason' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_before' => 'required|numeric',
            'items.*.quantity_after' => 'required|numeric',
            'items.*.reason' => 'required|string',
        ]);

        return DB::transaction(function () use ($data) {
            $adjustment = StockAdjustment::create([
                'warehouse_id' => $data['warehouse_id'],
                'adjustment_number' => $data['adjustment_number'],
                'reason' => $data['reason'],
                'status' => 'pending_approval',
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $item['adjustment_id'] = $adjustment->id;
                $item['difference'] = $item['quantity_after'] - $item['quantity_before'];
                StockAdjustmentItem::create($item);
            }

            return $this->success($adjustment->load('warehouse', 'creator', 'items.product'), 'Stock adjustment created successfully', 201);
        });
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return $this->success($stockAdjustment->load(['warehouse', 'creator', 'approver', 'items.product']));
    }

    public function approve(Request $request, StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->status !== 'pending_approval') {
            return $this->error('Adjustment is not pending approval', 400);
        }

        return DB::transaction(function () use ($stockAdjustment) {
            foreach ($stockAdjustment->items as $item) {
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $stockAdjustment->warehouse_id)
                    ->first();

                if ($stock) {
                    $oldQuantity = $stock->quantity;
                    $stock->quantity = $item->quantity_after;
                    $stock->save();

                    // Create stock movement record
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $stockAdjustment->warehouse_id,
                        'movement_type' => 'adjustment',
                        'reference_type' => 'stock_adjustment',
                        'reference_id' => $stockAdjustment->id,
                        'quantity' => $item->difference,
                        'unit_cost' => $stock->average_cost,
                        'balance_after' => $item->quantity_after,
                        'employee_id' => auth()->user()?->employee_id,
                    ]);
                }
            }

            $stockAdjustment->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            return $this->success($stockAdjustment->load('warehouse', 'creator', 'approver', 'items.product'), 'Stock adjustment approved successfully');
        });
    }

    public function reject(Request $request, StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->status !== 'pending_approval') {
            return $this->error('Adjustment is not pending approval', 400);
        }

        $stockAdjustment->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return $this->success($stockAdjustment->load('warehouse', 'creator', 'approver', 'items.product'), 'Stock adjustment rejected successfully');
    }
}
