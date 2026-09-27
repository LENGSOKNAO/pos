<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Expense::with('company', 'branch', 'category', 'employee', 'paymentMethod', 'creator', 'approver');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('expense_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('expense_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $expenses = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($expenses);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'category_id' => 'required|exists:expense_categories,id',
            'employee_id' => 'required|exists:employees,id',
            'expense_number' => 'required|string|unique:expenses,expense_number|max:50',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'expense_date' => 'required|date',
            'status' => 'required|in:draft,pending_approval,approved,paid,rejected,cancelled',
        ]);

        $expense = Expense::create([
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'],
            'category_id' => $data['category_id'],
            'employee_id' => $data['employee_id'],
            'expense_number' => $data['expense_number'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'payment_method_id' => $data['payment_method_id'],
            'expense_date' => $data['expense_date'],
            'status' => $data['status'],
            'created_by' => auth()->id(),
        ]);

        return $this->success($expense->load('category', 'branch', 'employee', 'paymentMethod'), 'Expense created successfully', 201);
    }

    public function show(Expense $expense)
    {
        return $this->success($expense->load('company', 'branch', 'category', 'employee', 'paymentMethod', 'creator', 'approver'));
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->status !== 'draft' && $expense->status !== 'pending_approval') {
            return $this->error('Cannot update expense in current status', 400);
        }

        $data = $this->validateRequest($request, [
            'branch_id' => 'sometimes|exists:branches,id',
            'category_id' => 'sometimes|exists:expense_categories,id',
            'employee_id' => 'sometimes|exists:employees,id',
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method_id' => 'sometimes|exists:payment_methods,id',
            'expense_date' => 'sometimes|date',
            'status' => 'sometimes|in:draft,pending_approval,approved,paid,rejected,cancelled',
        ]);

        $expense->update($data);

        return $this->success($expense->load('category', 'branch', 'employee', 'paymentMethod'), 'Expense updated successfully');
    }

    public function approve(Request $request, Expense $expense)
    {
        if ($expense->status !== 'pending_approval') {
            return $this->error('Expense is not pending approval', 400);
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return $this->success($expense, 'Expense approved successfully');
    }

    public function reject(Request $request, Expense $expense)
    {
        if ($expense->status !== 'pending_approval') {
            return $this->error('Expense is not pending approval', 400);
        }

        $expense->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return $this->success($expense, 'Expense rejected successfully');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->status !== 'draft' && $expense->status !== 'pending_approval') {
            return $this->error('Cannot delete expense in current status', 400);
        }

        $expense->delete();

        return $this->success(null, 'Expense deleted successfully');
    }
}
