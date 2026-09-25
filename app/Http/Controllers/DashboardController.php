<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $todaySalesTotal = (float) Sale::whereDate('created_at', $today)->sum('grand_total');
        $todayOrderCount = Sale::whereDate('created_at', $today)->count();
        $yesterdaySalesTotal = (float) Sale::whereDate('created_at', $yesterday)->sum('grand_total');
        $yesterdayOrderCount = Sale::whereDate('created_at', $yesterday)->count();
        $lowStockCount = Stock::where('quantity', '<=', 5)->count();
        $totalProducts = Product::count();

        $salesDelta = $yesterdaySalesTotal > 0
            ? round(($todaySalesTotal - $yesterdaySalesTotal) / $yesterdaySalesTotal * 100, 1)
            : ($todaySalesTotal > 0 ? 100.0 : 0.0);

        $weekSeries = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $weekSeries[] = [
                'day' => now()->subDays($i)->format('D'),
                'total' => (float) Sale::whereDate('created_at', $date)->sum('grand_total'),
            ];
        }

        $recentSales = Sale::query()
            ->with('customer:id,name')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'invoice_no', 'customer_id', 'grand_total', 'paid_amount', 'created_at'])
            ->map(fn (Sale $sale): array => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'customer' => $sale->customer?->name ?? 'Walk-in',
                'total' => (float) $sale->grand_total,
                'due' => max(0, (float) $sale->grand_total - (float) $sale->paid_amount),
                'time' => $sale->created_at?->format('H:i'),
            ]);

        $topProducts = DB::table('sale_items')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sale_items.created_at', '>=', now()->subDays(7))
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name as name, SUM(sale_items.quantity) as qty, SUM(sale_items.line_total) as revenue')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'qty' => (float) $row->qty,
                'revenue' => (float) $row->revenue,
            ]);

        $lowStockItems = Stock::query()
            ->with(['productUnit.product:id,name', 'productUnit.unit:id,short_name', 'warehouse:id,name'])
            ->where('quantity', '<=', 5)
            ->orderBy('quantity')
            ->limit(6)
            ->get()
            ->map(fn (Stock $stock): array => [
                'product' => $stock->productUnit?->product?->name ?? '—',
                'unit' => $stock->productUnit?->unit?->short_name,
                'warehouse' => $stock->warehouse?->name,
                'quantity' => (float) $stock->quantity,
            ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'todaySalesTotal' => $todaySalesTotal,
                'todayOrderCount' => $todayOrderCount,
                'yesterdaySalesTotal' => $yesterdaySalesTotal,
                'yesterdayOrderCount' => $yesterdayOrderCount,
                'salesDelta' => $salesDelta,
                'lowStockCount' => $lowStockCount,
                'totalProducts' => $totalProducts,
                'weekSeries' => $weekSeries,
                'recentSales' => $recentSales,
                'topProducts' => $topProducts,
                'lowStockItems' => $lowStockItems,
            ],
        ]);
    }
}
