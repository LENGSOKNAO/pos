<?php

namespace App\Http\Controllers\Api\V1\Promotion;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Coupon::with('promotion');

        if ($request->has('promotion_id')) {
            $query->where('promotion_id', $request->promotion_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('code', 'like', "%{$search}%");
        }

        $coupons = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($coupons);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'promotion_id' => 'required|exists:promotions,id',
            'code' => 'required|string|unique:coupons,code|max:50',
            'usage_limit' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive,expired',
        ]);

        $coupon = Coupon::create($data);

        return $this->success($coupon->load('promotion'), 'Coupon created successfully', 201);
    }

    public function show(Coupon $coupon)
    {
        return $this->success($coupon->load('promotion'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validateRequest($request, [
            'code' => 'sometimes|string|unique:coupons,code,'.$coupon->id.'|max:50',
            'usage_limit' => 'nullable|integer|min:1',
            'status' => 'sometimes|in:active,inactive,expired',
        ]);

        $coupon->update($data);

        return $this->success($coupon->load('promotion'), 'Coupon updated successfully');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return $this->success(null, 'Coupon deleted successfully');
    }
}
