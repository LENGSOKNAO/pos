<?php

namespace App\Http\Controllers\Api\V1\Promotion;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Promotion;
use App\Models\PromotionProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Promotion::with('company', 'products');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $promotions = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($promotions);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:percentage_discount,fixed_discount,buy_x_get_y,free_shipping',
            'value' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        return DB::transaction(function () use ($data) {
            $productIds = $data['product_ids'] ?? [];
            unset($data['product_ids']);

            $promotion = Promotion::create($data);

            if (! empty($productIds)) {
                foreach ($productIds as $productId) {
                    PromotionProduct::create([
                        'promotion_id' => $promotion->id,
                        'product_id' => $productId,
                    ]);
                }
            }

            return $this->success($promotion->load('products'), 'Promotion created successfully', 201);
        });
    }

    public function show(Promotion $promotion)
    {
        return $this->success($promotion->load(['company', 'products', 'coupons']));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $data = $this->validateRequest($request, [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:percentage_discount,fixed_discount,buy_x_get_y,free_shipping',
            'value' => 'sometimes|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $productIds = $data['product_ids'] ?? null;
        unset($data['product_ids']);

        $promotion->update($data);

        if ($productIds !== null) {
            $promotion->products()->sync($productIds);
        }

        return $this->success($promotion->load('products'), 'Promotion updated successfully');
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return $this->success(null, 'Promotion deleted successfully');
    }
}
