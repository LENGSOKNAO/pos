<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');

        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'unit:id,name,short_name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $items = collect($products->items())->map(function ($product): array {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category?->name,
                'brand' => $product->brand?->name,
                'unit' => $product->unit?->short_name ?? $product->unit?->name,
                'selling_price' => (float) $product->sale_price,
                'stock_qty' => 0,
            ];
        });

        // Correct stock qty via stocks join through product_units
        $productIds = $products->pluck('id');
        $stockSums = \DB::table('stocks')
            ->join('product_units', 'product_units.id', '=', 'stocks.product_unit_id')
            ->whereIn('product_units.product_id', $productIds)
            ->groupBy('product_units.product_id')
            ->selectRaw('product_units.product_id as product_id, SUM(stocks.quantity) as qty')
            ->pluck('qty', 'product_id');

        $items = $items->map(fn (array $row): array => [
            ...$row,
            'stock_qty' => (float) ($stockSums[$row['id']] ?? 0),
        ]);

        return Inertia::render('products/index', [
            'products' => $items,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
            'filters' => ['search' => $search],
        ]);
    }
}
