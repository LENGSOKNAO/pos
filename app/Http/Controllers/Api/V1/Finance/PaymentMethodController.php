<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = PaymentMethod::with('company');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $methods = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return $this->paginated($methods);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:cash,card,bank_transfer,khqr,mobile_money,cheque',
            'provider' => 'nullable|string|max:100',
            'fee_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $method = PaymentMethod::create($data);

        return $this->success($method->load('company'), 'Payment method created successfully', 201);
    }

    public function show(PaymentMethod $paymentMethod)
    {
        return $this->success($paymentMethod->load('company'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $data = $this->validateRequest($request, [
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|string|in:cash,card,bank_transfer,khqr,mobile_money,cheque',
            'provider' => 'nullable|string|max:100',
            'fee_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $paymentMethod->update($data);

        return $this->success($paymentMethod->load('company'), 'Payment method updated successfully');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return $this->success(null, 'Payment method deleted successfully');
    }
}
