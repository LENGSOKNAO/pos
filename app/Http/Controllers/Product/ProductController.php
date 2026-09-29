<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::with(['category', 'brand', 'unit']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('track_batch')) {
            $query->where('track_batch', $request->boolean('track_batch'));
        }

        if ($request->has('track_expiry')) {
            $query->where('track_expiry', $request->boolean('track_expiry'));
        }

        if ($request->has('track_serial')) {
            $query->where('track_serial', $request->boolean('track_serial'));
        }

        $categories = Category::where('status', 'active')->orderBy('name')->get();
        $brands = Brand::where('status', 'active')->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $companies = Company::where('status', 'active')->orderBy('name')->get();

        return Inertia::render('products/index', [
            'products' => Inertia::defer(fn () => $query->latest()->paginate($request->get('per_page', 15))),
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
            'companies' => $companies,
        ]);
    }

    public function create()
    {
        $categories = Category::where('status', 'active')->orderBy('name')->get();
        $brands = Brand::where('status', 'active')->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $companies = Company::where('status', 'active')->orderBy('name')->get();

        return Inertia::render('products/create', [
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
            'companies' => $companies,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'nullable|exists:units,id',
            'sku' => 'required|string|unique:products,sku|max:100',
            'barcode' => 'nullable|string|unique:products,barcode|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'vip_price' => 'nullable|numeric|min:0',
            'minimum_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'track_batch' => 'boolean',
            'track_expiry' => 'boolean',
            'track_serial' => 'boolean',
            'status' => 'required|in:active,inactive,discontinued',
        ]);

        $product = Product::create($data);

        return redirect()->route('products.index')->with('success', 'Product created successfully');
    }

    public function show(Product $product)
    {
        return Inertia::render('products/show', [
            'product' => $product->load(['category', 'brand', 'unit', 'variants', 'batches', 'serials', 'stock']),
        ]);
    }

    public function edit(Product $product)
    {
        $categories = Category::where('status', 'active')->orderBy('name')->get();
        $brands = Brand::where('status', 'active')->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $companies = Company::where('status', 'active')->orderBy('name')->get();

        return Inertia::render('products/edit', [
            'product' => $product->load(['category', 'brand', 'unit']),
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
            'companies' => $companies,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'nullable|exists:units,id',
            'sku' => 'sometimes|string|unique:products,sku,'.$product->id.'|max:100',
            'barcode' => 'nullable|string|unique:products,barcode,'.$product->id.'|max:100',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'vip_price' => 'nullable|numeric|min:0',
            'minimum_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'track_batch' => 'boolean',
            'track_expiry' => 'boolean',
            'track_serial' => 'boolean',
            'status' => 'sometimes|in:active,inactive,discontinued',
        ]);

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully');
    }
}
