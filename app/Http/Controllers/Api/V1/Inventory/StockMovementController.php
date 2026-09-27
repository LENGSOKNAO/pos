<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = StockMovement::with(['product', 'warehouse', 'batch', 'employee']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->has('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->has('reference_id')) {
            $query->where('reference_id', $request->reference_id);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $movements = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($movements);
    }

    public function show(StockMovement $stockMovement)
    {
        return $this->success($stockMovement->load(['product', 'warehouse', 'batch', 'employee']));
    }
}
