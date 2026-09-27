<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\ProductSerial;
use Illuminate\Http\Request;

class ProductSerialController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = ProductSerial::with('product', 'warehouse');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $serials = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($serials);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'serial_number' => 'required|string|max:100',
            'status' => 'required|in:available,sold,reserved,damaged',
        ]);

        $serial = ProductSerial::create($data);

        return $this->success($serial->load('product', 'warehouse'), 'Serial created successfully', 201);
    }

    public function show(ProductSerial $productSerial)
    {
        return $this->success($productSerial->load(['product', 'warehouse']));
    }

    public function update(Request $request, ProductSerial $productSerial)
    {
        $data = $this->validateRequest($request, [
            'product_id' => 'sometimes|exists:products,id',
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'serial_number' => 'sometimes|string|max:100',
            'status' => 'sometimes|in:available,sold,reserved,damaged',
            'sold_at' => 'nullable|date',
        ]);

        $productSerial->update($data);

        return $this->success($productSerial->load('product', 'warehouse'), 'Serial updated successfully');
    }

    public function destroy(ProductSerial $productSerial)
    {
        $productSerial->delete();

        return $this->success(null, 'Serial deleted successfully');
    }
}
