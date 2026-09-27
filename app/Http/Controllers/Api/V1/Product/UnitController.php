<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Unit::with('company');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        $units = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return $this->paginated($units);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:20',
        ]);

        $unit = Unit::create($data);

        return $this->success($unit->load('company'), 'Unit created successfully', 201);
    }

    public function show(Unit $unit)
    {
        return $this->success($unit->load(['company', 'products']));
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'sometimes|exists:companies,id',
            'name' => 'sometimes|string|max:255',
            'symbol' => 'sometimes|string|max:20',
        ]);

        $unit->update($data);

        return $this->success($unit->load('company'), 'Unit updated successfully');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return $this->success(null, 'Unit deleted successfully');
    }
}
