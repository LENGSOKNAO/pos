<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Category::with('company', 'parent');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } elseif ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
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

        $categories = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return $this->paginated($categories);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $category = Category::create($data);

        return $this->success($category->load('company', 'parent'), 'Category created successfully', 201);
    }

    public function show(Category $category)
    {
        return $this->success($category->load(['company', 'parent', 'children', 'products']));
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'sometimes|exists:companies,id',
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $category->update($data);

        return $this->success($category->load('company', 'parent'), 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return $this->success(null, 'Category deleted successfully');
    }
}
