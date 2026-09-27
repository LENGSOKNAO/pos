<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Warehouse::with('branch');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
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

        $warehouses = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($warehouses);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'branch_id' => 'required|exists:branches,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'manager_id' => 'nullable|exists:employees,id',
            'status' => 'required|in:active,inactive',
        ]);

        $warehouse = Warehouse::create($data);

        return $this->success($warehouse->load('branch', 'manager'), 'Warehouse created successfully', 201);
    }

    public function show(Warehouse $warehouse)
    {
        return $this->success($warehouse->load(['branch', 'manager', 'locations', 'stock']));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $this->validateRequest($request, [
            'branch_id' => 'sometimes|exists:branches,id',
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'manager_id' => 'nullable|exists:employees,id',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $warehouse->update($data);

        return $this->success($warehouse->load('branch', 'manager'), 'Warehouse updated successfully');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return $this->success(null, 'Warehouse deleted successfully');
    }
}
