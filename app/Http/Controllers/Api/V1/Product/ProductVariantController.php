<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = ProductVariant::with('product');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('variant_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $variants = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($variants);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|unique:product_variants,sku|max:100',
            'barcode' => 'nullable|string|unique:product_variants,barcode|max:100',
            'variant_name' => 'required|string|max:255',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $variant = ProductVariant::create($data);

        return $this->success($variant->load('product'), 'Variant created successfully', 201);
    }

    public function show(ProductVariant $productVariant)
    {
        return $this->success($productVariant->load('product'));
    }

    public function update(Request $request, ProductVariant $productVariant)
    {
        $data = $this->validateRequest($request, [
            'product_id' => 'sometimes|exists:products,id',
            'sku' => 'sometimes|string|unique:product_variants,sku,'.$productVariant->id.'|max:100',
            'barcode' => 'nullable|string|unique:product_variants,barcode,'.$productVariant->id.'|max:100',
            'variant_name' => 'sometimes|string|max:255',
            'cost_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $productVariant->update($data);

        return $this->success($productVariant->load('product'), 'Variant updated successfully');
    }

    public function destroy(ProductVariant $productVariant)
    {
        $productVariant->delete();

        return $this->success(null, 'Variant deleted successfully');
    }
}
