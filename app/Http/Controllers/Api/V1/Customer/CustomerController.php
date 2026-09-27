<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Customer::with('customerGroup');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('customer_group_id')) {
            $query->where('customer_group_id', $request->customer_group_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($customers);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'customer_code' => 'required|string|unique:customers,customer_code|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive,blocked',
        ]);

        $customer = Customer::create($data);

        return $this->success($customer->load('customerGroup'), 'Customer created successfully', 201);
    }

    public function show(Customer $customer)
    {
        return $this->success($customer->load(['customerGroup', 'invoices', 'payments', 'salesReturns', 'loyaltyTransactions', 'deliveryOrders', 'quotations']));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validateRequest($request, [
            'customer_code' => 'sometimes|string|unique:customers,customer_code,'.$customer->id.'|max:50',
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit' => 'sometimes|numeric|min:0',
            'credit_days' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:active,inactive,blocked',
        ]);

        $customer->update($data);

        return $this->success($customer->load('customerGroup'), 'Customer updated successfully');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return $this->success(null, 'Customer deleted successfully');
    }

    public function statement(Customer $customer)
    {
        $invoices = $customer->invoices()->with('payments')->latest()->get();
        $payments = $customer->payments()->with('invoice')->latest()->get();

        $statement = [
            'customer' => $customer,
            'total_invoices' => $invoices->sum('total'),
            'total_paid' => $payments->sum('amount'),
            'outstanding_balance' => $invoices->sum('due_amount'),
            'invoices' => $invoices,
            'payments' => $payments,
        ];

        return $this->success($statement);
    }

    public function loyalty(Customer $customer)
    {
        $transactions = $customer->loyaltyTransactions()->latest()->paginate(20);

        return $this->paginated($transactions);
    }
}
