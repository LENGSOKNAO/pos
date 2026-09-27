<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Company::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $companies = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($companies);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'code' => 'required|string|unique:companies,code|max:50',
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:100',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $company = Company::create($data);

        return $this->success($company, 'Company created successfully', 201);
    }

    public function show(Company $company)
    {
        return $this->success($company->load(['branches', 'warehouses']));
    }

    public function update(Request $request, Company $company)
    {
        $data = $this->validateRequest($request, [
            'code' => 'sometimes|string|unique:companies,code,'.$company->id.'|max:50',
            'name' => 'sometimes|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:100',
            'currency' => 'sometimes|string|size:3',
            'timezone' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $company->update($data);

        return $this->success($company, 'Company updated successfully');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return $this->success(null, 'Company deleted successfully');
    }
}
