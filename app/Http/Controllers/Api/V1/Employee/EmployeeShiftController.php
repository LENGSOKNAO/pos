<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\EmployeeShift;
use Illuminate\Http\Request;

class EmployeeShiftController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = EmployeeShift::with('employee', 'branch');

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('start_time', '<=', $request->date_to);
        }

        $shifts = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($shifts);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'employee_id' => 'required|exists:employees,id',
            'branch_id' => 'required|exists:branches,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        $shift = EmployeeShift::create($data);

        return $this->success($shift->load('employee', 'branch'), 'Shift created successfully', 201);
    }

    public function show(EmployeeShift $employeeShift)
    {
        return $this->success($employeeShift->load('employee', 'branch'));
    }

    public function update(Request $request, EmployeeShift $employeeShift)
    {
        $data = $this->validateRequest($request, [
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
        ]);

        $employeeShift->update($data);

        return $this->success($employeeShift->load('employee', 'branch'), 'Shift updated successfully');
    }

    public function destroy(EmployeeShift $employeeShift)
    {
        $employeeShift->delete();

        return $this->success(null, 'Shift deleted successfully');
    }
}
