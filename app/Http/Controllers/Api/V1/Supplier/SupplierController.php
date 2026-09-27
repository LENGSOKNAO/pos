<?php

namespace App\Http\Controllers\Api\V1\Supplier;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Supplier::with('company');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('supplier_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($suppliers);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'supplier_code' => 'required|string|unique:suppliers,supplier_code|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $supplier = Supplier::create($data);

        return $this->success($supplier, 'Supplier created successfully', 201);
    }

    public function show(Supplier $supplier)
    {
        return $this->success($supplier->load(['company', 'purchaseOrders', 'purchaseReturns', 'supplierPayments']));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $this->validateRequest($request, [
            'supplier_code' => 'sometimes|string|unique:suppliers,supplier_code,'.$supplier->id.'|max:50',
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $supplier->update($data);

        return $this->success($supplier, 'Supplier updated successfully');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return $this->success(null, 'Supplier deleted successfully');
    }

    public function statement(Supplier $supplier)
    {
        $purchaseOrders = $supplier->purchaseOrders()->with('receipts')->latest()->get();
        $payments = $supplier->supplierPayments()->latest()->get();

        $statement = [
            'supplier' => $supplier,
            'total_purchases' => $purchaseOrders->sum('total'),
            'total_paid' => $payments->sum('amount'),
            'outstanding_balance' => $purchaseOrders->sum('total') - $payments->sum('amount'),
            'purchase_orders' => $purchaseOrders,
            'payments' => $payments,
        ];

        return $this->success($statement);
    }
}
