<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\ProductBatch;
use Illuminate\Http\Request;

class ProductBatchController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = ProductBatch::with('product', 'warehouse');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('expiring_only')) {
            $query->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>=', now());
        }

        if ($request->boolean('expired_only')) {
            $query->whereNotNull('expiry_date')
                ->where('expiry_date', '<', now());
        }

        $batches = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($batches);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'batch_number' => 'required|string|max:100',
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'cost_price' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,expired',
        ]);

        $batch = ProductBatch::create($data);

        return $this->success($batch->load('product', 'warehouse'), 'Batch created successfully', 201);
    }

    public function show(ProductBatch $productBatch)
    {
        return $this->success($productBatch->load(['product', 'warehouse', 'stockMovements', 'purchaseReceiptItems']));
    }

    public function update(Request $request, ProductBatch $productBatch)
    {
        $data = $this->validateRequest($request, [
            'product_id' => 'sometimes|exists:products,id',
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'batch_number' => 'sometimes|string|max:100',
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'cost_price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive,expired',
        ]);

        $productBatch->update($data);

        return $this->success($productBatch->load('product', 'warehouse'), 'Batch updated successfully');
    }

    public function destroy(ProductBatch $productBatch)
    {
        $productBatch->delete();

        return $this->success(null, 'Batch deleted successfully');
    }
}
