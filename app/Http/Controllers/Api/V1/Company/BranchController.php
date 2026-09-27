<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Branch::with('company');

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

        $branches = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($branches);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'manager_id' => 'nullable|exists:employees,id',
            'status' => 'required|in:active,inactive',
            'opened_at' => 'nullable|date',
        ]);

        $branch = Branch::create($data);

        return $this->success($branch->load('company', 'manager'), 'Branch created successfully', 201);
    }

    public function show(Branch $branch)
    {
        return $this->success($branch->load(['company', 'manager', 'warehouses', 'employees']));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'sometimes|exists:companies,id',
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'manager_id' => 'nullable|exists:employees,id',
            'status' => 'sometimes|in:active,inactive',
            'opened_at' => 'nullable|date',
        ]);

        $branch->update($data);

        return $this->success($branch->load('company', 'manager'), 'Branch updated successfully');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return $this->success(null, 'Branch deleted successfully');
    }
}
