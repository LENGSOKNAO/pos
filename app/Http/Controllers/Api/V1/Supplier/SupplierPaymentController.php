<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class SupplierPaymentController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = SupplierPayment::with('supplier', 'paymentMethod', 'payer');

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->has('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }

        $payments = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($payments);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number' => 'nullable|string',
        ]);

        $payment = SupplierPayment::create([
            'supplier_id' => $data['supplier_id'],
            'amount' => $data['amount'],
            'payment_method_id' => $data['payment_method_id'],
            'payment_date' => now(),
            'reference_number' => $data['reference_number'],
            'paid_by' => auth()->id(),
        ]);

        return $this->success($payment->load('supplier', 'paymentMethod'), 'Supplier payment recorded successfully', 201);
    }

    public function show(SupplierPayment $supplierPayment)
    {
        return $this->success($supplierPayment->load('supplier', 'paymentMethod', 'payer'));
    }
}
