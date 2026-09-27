<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Brand::with('company');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $brands = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return $this->paginated($brands);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $brand = Brand::create($data);

        return $this->success($brand->load('company'), 'Brand created successfully', 201);
    }

    public function show(Brand $brand)
    {
        return $this->success($brand->load(['company', 'products']));
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'sometimes|exists:companies,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $brand->update($data);

        return $this->success($brand->load('company'), 'Brand updated successfully');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return $this->success(null, 'Brand deleted successfully');
    }
}
