<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\EmployeeAttendance;
use Illuminate\Http\Request;

class EmployeeAttendanceController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = EmployeeAttendance::with('employee', 'branch');

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
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $attendance = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($attendance);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'employee_id' => 'required|exists:employees,id',
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
            'clock_in' => 'nullable|date',
            'clock_out' => 'nullable|date',
            'status' => 'required|in:present,absent,late,early_leave,on_leave',
        ]);

        $attendance = EmployeeAttendance::create($data);

        return $this->success($attendance->load('employee', 'branch'), 'Attendance recorded successfully', 201);
    }

    public function show(EmployeeAttendance $employeeAttendance)
    {
        return $this->success($employeeAttendance->load('employee', 'branch'));
    }

    public function update(Request $request, EmployeeAttendance $employeeAttendance)
    {
        $data = $this->validateRequest($request, [
            'clock_in' => 'nullable|date',
            'clock_out' => 'nullable|date',
            'status' => 'sometimes|in:present,absent,late,early_leave,on_leave',
        ]);

        $employeeAttendance->update($data);

        return $this->success($employeeAttendance->load('employee', 'branch'), 'Attendance updated successfully');
    }

    public function destroy(EmployeeAttendance $employeeAttendance)
    {
        $employeeAttendance->delete();

        return $this->success(null, 'Attendance record deleted successfully');
    }
}
