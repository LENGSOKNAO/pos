<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\ProductBatch;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Stock::with(['product', 'warehouse', 'location']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $stock = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($stock);
    }

    public function show(Stock $stock)
    {
        return $this->success($stock->load(['product', 'warehouse', 'location']));
    }

    public function lowStock(Request $request)
    {
        $query = Stock::with(['product', 'warehouse', 'location'])
            ->whereRaw('quantity - reserved_quantity <= products.reorder_level')
            ->join('products', 'stock.product_id', '=', 'products.id');

        if ($request->has('warehouse_id')) {
            $query->where('stock.warehouse_id', $request->warehouse_id);
        }

        $lowStock = $query->select('stock.*')->paginate($request->get('per_page', 15));

        return $this->paginated($lowStock);
    }

    public function expiring(Request $request)
    {
        $query = ProductBatch::with('product', 'warehouse')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>=', now())
            ->where('status', 'active');

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $expiring = $query->latest('expiry_date')->paginate($request->get('per_page', 15));

        return $this->paginated($expiring);
    }

    public function update(Request $request, Stock $stock)
    {
        $data = $this->validateRequest($request, [
            'quantity' => 'sometimes|numeric',
            'reserved_quantity' => 'sometimes|numeric',
            'damaged_quantity' => 'sometimes|numeric',
            'average_cost' => 'sometimes|numeric|min:0',
        ]);

        $stock->update($data);

        return $this->success($stock->load(['product', 'warehouse', 'location']), 'Stock updated successfully');
    }
}
