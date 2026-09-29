<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
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

        $daily = Invoice::query()
            ->where('status', 'paid')
            ->whereDate('invoice_date', '>=', now()->subDays(6)->toDateString())
            ->groupBy('invoice_date')
            ->selectRaw('DATE(invoice_date) as day, SUM(total) as total, COUNT(*) as orders')
            ->pluck('total', 'day')
            ->all();
        $dailyCount = Invoice::query()
            ->where('status', 'paid')
            ->whereDate('invoice_date', '>=', now()->subDays(6)->toDateString())
            ->groupBy('invoice_date')
            ->selectRaw('DATE(invoice_date) as day, COUNT(*) as orders')
            ->pluck('orders', 'day')
            ->all();

        $todaySalesTotal = (float) ($daily[$today] ?? 0);
        $todayOrderCount = (int) ($dailyCount[$today] ?? 0);
        $yesterdaySalesTotal = (float) ($daily[$yesterday] ?? 0);
        $yesterdayOrderCount = (int) ($dailyCount[$yesterday] ?? 0);
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
                'total' => (float) ($daily[$date] ?? 0),
            ];
        }

        $recentSales = Invoice::query()
            ->with('customer:id,name')
            ->where('status', 'paid')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'invoice_number', 'customer_id', 'total', 'paid_amount', 'created_at'])
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_number,
                'customer' => $invoice->customer?->name ?? 'Walk-in',
                'total' => (float) $invoice->total,
                'due' => max(0, (float) $invoice->total - (float) $invoice->paid_amount),
                'time' => $invoice->created_at?->format('H:i'),
            ]);

        $topProducts = DB::table('invoice_items')
            ->join('products', 'products.id', '=', 'invoice_items.product_id')
            ->where('invoice_items.created_at', '>=', now()->subDays(7))
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name as name, SUM(invoice_items.quantity) as qty, SUM(invoice_items.total) as revenue')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'qty' => (float) $row->qty,
                'revenue' => (float) $row->revenue,
            ]);

        $lowStockItems = Stock::query()
            ->with(['product:id,name,unit_id', 'product.unit:id,symbol', 'warehouse:id,name'])
            ->where('quantity', '<=', 5)
            ->orderBy('quantity')
            ->limit(6)
            ->get()
            ->map(fn (Stock $stock): array => [
                'product' => $stock->product?->name ?? '—',
                'unit' => $stock->product?->unit?->symbol,
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
