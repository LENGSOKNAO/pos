<?php

namespace App\Http\Controllers\Api\V1\Purchasing;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReceiptController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = PurchaseReceipt::with('purchaseOrder', 'warehouse', 'receiver', 'items.product', 'items.batch');

        if ($request->has('purchase_order_id')) {
            $query->where('purchase_order_id', $request->purchase_order_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('received_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('received_at', '<=', $request->date_to);
        }

        $receipts = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($receipts);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_id' => 'nullable|exists:product_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $purchaseOrder = PurchaseOrder::findOrFail($data['purchase_order_id']);

            $receipt = PurchaseReceipt::create([
                'purchase_order_id' => $data['purchase_order_id'],
                'warehouse_id' => $data['warehouse_id'],
                'receipt_number' => 'PR-'.now()->format('YmdHis').'-'.rand(1000, 9999),
                'received_by' => auth()->id(),
                'status' => 'completed',
            ]);

            foreach ($data['items'] as $item) {
                $poItem = $purchaseOrder->items()->where('product_id', $item['product_id'])->first();

                if (! $poItem) {
                    return $this->error('Product not found in purchase order', 400);
                }

                if (($poItem->received_quantity + $item['quantity']) > $poItem->quantity) {
                    return $this->error("Received quantity exceeds ordered quantity for product {$item['product_id']}", 400);
                }

                PurchaseReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'product_id' => $item['product_id'],
                    'batch_id' => $item['batch_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                ]);

                // Update PO item received quantity
                $poItem->received_quantity += $item['quantity'];
                $poItem->save();

                // Update stock
                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->first();

                if ($stock) {
                    $stock->quantity += $item['quantity'];
                    $stock->save();
                } else {
                    $stock = Stock::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $data['warehouse_id'],
                        'quantity' => $item['quantity'],
                        'reserved_quantity' => 0,
                        'damaged_quantity' => 0,
                        'average_cost' => $item['unit_cost'],
                    ]);
                }

                // Create batch if needed
                if ($item['batch_id']) {
                    $batch = ProductBatch::find($item['batch_id']);
                    if ($batch) {
                        $batch->quantity += $item['quantity'];
                        $batch->save();
                    }
                }

                // Create stock movement
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'batch_id' => $item['batch_id'],
                    'movement_type' => 'in',
                    'reference_type' => 'purchase_receipt',
                    'reference_id' => $receipt->id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'balance_after' => $stock->quantity,
                    'employee_id' => auth()->user()?->employee_id,
                ]);
            }

            // Update PO status
            $totalReceived = $purchaseOrder->items->sum('received_quantity');
            $totalOrdered = $purchaseOrder->items->sum('quantity');

            if ($totalReceived >= $totalOrdered) {
                $purchaseOrder->update(['status' => 'received']);
            } elseif ($totalReceived > 0) {
                $purchaseOrder->update(['status' => 'partial_received']);
            }

            return $this->success($receipt->load('items.product', 'items.batch'), 'Purchase receipt created successfully', 201);
        });
    }

    public function show(PurchaseReceipt $purchaseReceipt)
    {
        return $this->success($purchaseReceipt->load(['purchaseOrder', 'warehouse', 'receiver', 'items.product', 'items.batch']));
    }
}
