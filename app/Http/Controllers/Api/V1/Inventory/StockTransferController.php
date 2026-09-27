<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = StockTransfer::with('fromWarehouse', 'toWarehouse', 'creator', 'approver', 'items.product');

        if ($request->has('from_warehouse_id')) {
            $query->where('from_warehouse_id', $request->from_warehouse_id);
        }

        if ($request->has('to_warehouse_id')) {
            $query->where('to_warehouse_id', $request->to_warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('transfer_number', 'like', "%{$search}%");
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $transfers = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($transfers);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'transfer_number' => 'required|string|unique:stock_transfers,transfer_number|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        return DB::transaction(function () use ($data) {
            $transfer = StockTransfer::create([
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'transfer_number' => $data['transfer_number'],
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $item['transfer_id'] = $transfer->id;
                StockTransferItem::create($item);
            }

            return $this->success($transfer->load('fromWarehouse', 'toWarehouse', 'creator', 'items.product'), 'Stock transfer created successfully', 201);
        });
    }

    public function show(StockTransfer $stockTransfer)
    {
        return $this->success($stockTransfer->load(['fromWarehouse', 'toWarehouse', 'creator', 'approver', 'items.product']));
    }

    public function receive(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'pending' && $stockTransfer->status !== 'in_transit') {
            return $this->error('Transfer cannot be received in current status', 400);
        }

        return DB::transaction(function () use ($stockTransfer) {
            foreach ($stockTransfer->items as $item) {
                // Deduct from source warehouse
                $fromStock = Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $stockTransfer->from_warehouse_id)
                    ->first();

                if (! $fromStock || $fromStock->quantity < $item->quantity) {
                    return $this->error("Insufficient stock for product {$item->product->name} in source warehouse", 400);
                }

                $fromStock->quantity -= $item->quantity;
                $fromStock->save();

                // Add to destination warehouse
                $toStock = Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $stockTransfer->to_warehouse_id)
                    ->first();

                if ($toStock) {
                    $toStock->quantity += $item->quantity;
                    $toStock->save();
                } else {
                    $toStock = Stock::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $stockTransfer->to_warehouse_id,
                        'quantity' => $item->quantity,
                        'reserved_quantity' => 0,
                        'damaged_quantity' => 0,
                        'average_cost' => $item->product->cost_price,
                    ]);
                }

                // Create stock movements
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $stockTransfer->from_warehouse_id,
                    'movement_type' => 'transfer_out',
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $stockTransfer->id,
                    'quantity' => -$item->quantity,
                    'unit_cost' => $fromStock->average_cost,
                    'balance_after' => $fromStock->quantity,
                    'employee_id' => auth()->user()?->employee_id,
                ]);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $stockTransfer->to_warehouse_id,
                    'movement_type' => 'transfer_in',
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $stockTransfer->id,
                    'quantity' => $item->quantity,
                    'unit_cost' => $fromStock->average_cost,
                    'balance_after' => $toStock->quantity,
                    'employee_id' => auth()->user()?->employee_id,
                ]);
            }

            $stockTransfer->update([
                'status' => 'received',
                'transferred_at' => now(),
            ]);

            return $this->success($stockTransfer->load('fromWarehouse', 'toWarehouse', 'creator', 'items.product'), 'Stock transfer received successfully');
        });
    }
}
