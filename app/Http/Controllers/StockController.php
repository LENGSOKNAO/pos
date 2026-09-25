<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(): Response
    {
        $stocks = Stock::query()
            ->with(['warehouse:id,name', 'productUnit:id,product_id,unit_id,barcode', 'productUnit.product:id,name,sku', 'productUnit.unit:id,name,short_name'])
            ->orderBy('id')
            ->paginate(20);

        $rows = collect($stocks->items())->map(fn (Stock $stock): array => [
            'id' => $stock->id,
            'warehouse' => $stock->warehouse?->name,
            'product' => $stock->productUnit?->product?->name,
            'sku' => $stock->productUnit?->barcode ?? $stock->productUnit?->product?->sku,
            'unit' => $stock->productUnit?->unit?->short_name ?? $stock->productUnit?->unit?->name,
            'quantity' => (float) $stock->quantity,
        ]);

        return Inertia::render('stocks/index', [
            'stocks' => $rows,
            'meta' => [
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
                'total' => $stocks->total(),
            ],
        ]);
    }
}
