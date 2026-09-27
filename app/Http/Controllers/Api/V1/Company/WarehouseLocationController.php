<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;

class WarehouseLocationController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = WarehouseLocation::with('warehouse');

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
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

        $locations = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($locations);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:shelf,bin,pallet,rack,floor',
            'status' => 'required|in:active,inactive',
        ]);

        $location = WarehouseLocation::create($data);

        return $this->success($location->load('warehouse'), 'Location created successfully', 201);
    }

    public function show(WarehouseLocation $warehouseLocation)
    {
        return $this->success($warehouseLocation->load(['warehouse', 'stock']));
    }

    public function update(Request $request, WarehouseLocation $warehouseLocation)
    {
        $data = $this->validateRequest($request, [
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:shelf,bin,pallet,rack,floor',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $warehouseLocation->update($data);

        return $this->success($warehouseLocation->load('warehouse'), 'Location updated successfully');
    }

    public function destroy(WarehouseLocation $warehouseLocation)
    {
        $warehouseLocation->delete();

        return $this->success(null, 'Location deleted successfully');
    }
}
