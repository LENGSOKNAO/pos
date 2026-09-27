<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\EmployeeCommission;
use Illuminate\Http\Request;

class EmployeeCommissionController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = EmployeeCommission::with('employee', 'invoice');

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $commissions = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($commissions);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'employee_id' => 'required|exists:employees,id',
            'invoice_id' => 'required|exists:invoices,id',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_amount' => 'required|numeric|min:0',
        ]);

        $commission = EmployeeCommission::create($data);

        return $this->success($commission->load('employee', 'invoice'), 'Commission recorded successfully', 201);
    }

    public function show(EmployeeCommission $employeeCommission)
    {
        return $this->success($employeeCommission->load('employee', 'invoice'));
    }
}
