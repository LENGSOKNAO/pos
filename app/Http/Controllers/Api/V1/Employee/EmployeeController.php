<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Employee::with('company', 'branch', 'user');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($employees);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'employee_code' => 'required|string|unique:employees,employee_code|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive,terminated',
            'create_user_account' => 'boolean',
            'username' => 'nullable|string|unique:users,username|max:100',
            'password' => 'nullable|string|min:8',
        ]);

        $employee = Employee::create($data);

        // Create user account if requested
        if ($data['create_user_account'] && isset($data['username']) && isset($data['password'])) {
            User::create([
                'employee_id' => $employee->id,
                'username' => $data['username'],
                'email' => $data['email'] ?? $employee->email,
                'password_hash' => Hash::make($data['password']),
                'status' => 'active',
            ]);
        }

        return $this->success($employee->load('company', 'branch', 'user'), 'Employee created successfully', 201);
    }

    public function show(Employee $employee)
    {
        return $this->success($employee->load(['company', 'branch', 'user', 'attendance', 'shifts', 'commissions']));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'sometimes|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'employee_code' => 'sometimes|string|unique:employees,employee_code,'.$employee->id.'|max:50',
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'sometimes|in:active,inactive,terminated',
        ]);

        $employee->update($data);

        return $this->success($employee->load('company', 'branch', 'user'), 'Employee updated successfully');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return $this->success(null, 'Employee deleted successfully');
    }
}
