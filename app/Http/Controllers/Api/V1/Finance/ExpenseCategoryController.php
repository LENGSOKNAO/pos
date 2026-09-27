<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = ExpenseCategory::with('company');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return $this->paginated($categories);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = ExpenseCategory::create($data);

        return $this->success($category->load('company'), 'Expense category created successfully', 201);
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        return $this->success($expenseCategory->load(['company', 'expenses']));
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $data = $this->validateRequest($request, [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $expenseCategory->update($data);

        return $this->success($expenseCategory->load('company'), 'Expense category updated successfully');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();

        return $this->success(null, 'Expense category deleted successfully');
    }
}
