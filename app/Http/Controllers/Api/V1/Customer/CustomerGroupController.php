<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;

class CustomerGroupController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = CustomerGroup::with('company');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $groups = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return $this->paginated($groups);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'discount_rate' => 'required|numeric|min:0|max:100',
            'price_level' => 'required|string|in:retail,wholesale,vip',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $group = CustomerGroup::create($data);

        return $this->success($group->load('company'), 'Customer group created successfully', 201);
    }

    public function show(CustomerGroup $customerGroup)
    {
        return $this->success($customerGroup->load(['company', 'customers']));
    }

    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'sometimes|exists:companies,id',
            'name' => 'sometimes|string|max:255',
            'discount_rate' => 'sometimes|numeric|min:0|max:100',
            'price_level' => 'sometimes|string|in:retail,wholesale,vip',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $customerGroup->update($data);

        return $this->success($customerGroup->load('company'), 'Customer group updated successfully');
    }

    public function destroy(CustomerGroup $customerGroup)
    {
        $customerGroup->delete();

        return $this->success(null, 'Customer group deleted successfully');
    }
}
