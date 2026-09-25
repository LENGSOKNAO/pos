<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $from = $validated['from'] ?? now()->subDays(30)->toDateString();
        $to = $validated['to'] ?? now()->toDateString();
        $branchId = $validated['branch_id'] ?? null;

        $salesQuery = Sale::whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
        $expenseQuery = Expense::whereDate('expense_date', '>=', $from)->whereDate('expense_date', '<=', $to);

        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
            $expenseQuery->where('branch_id', $branchId);
        }

        $revenue = (float) (clone $salesQuery)->sum('grand_total');
        $orderCount = (clone $salesQuery)->count();
        $avgTicket = $orderCount > 0 ? $revenue / $orderCount : 0;

        $cogsQuery = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereDate('sales.created_at', '>=', $from)
            ->whereDate('sales.created_at', '<=', $to);

        if ($branchId) {
            $cogsQuery->where('sales.branch_id', $branchId);
        }

        $cogs = (float) $cogsQuery->sum(DB::raw('sale_items.quantity * products.purchase_price'));
        $grossProfit = $revenue - $cogs;
        $expensesTotal = (float) (clone $expenseQuery)->sum('amount');
        $net = $grossProfit - $expensesTotal;

        $topProductsQuery = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereDate('sales.created_at', '>=', $from)
            ->whereDate('sales.created_at', '<=', $to);

        if ($branchId) {
            $topProductsQuery->where('sales.branch_id', $branchId);
        }

        $topProducts = $topProductsQuery
            ->select('products.name', 'products.sku', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.line_total) as total_sales'))
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $dailyQuery = Sale::whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);

        if ($branchId) {
            $dailyQuery->where('branch_id', $branchId);
        }

        $salesByDay = $dailyQuery
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(grand_total) as total'), DB::raw('COUNT(*) as orders'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get();

        return Inertia::render('reports/index', [
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId],
            'branches' => Branch::select('id', 'name')->orderBy('name')->get(),
            'summary' => [
                'revenue' => $revenue,
                'orderCount' => $orderCount,
                'avgTicket' => $avgTicket,
                'cogs' => $cogs,
                'grossProfit' => $grossProfit,
                'expensesTotal' => $expensesTotal,
                'net' => $net,
            ],
            'topProducts' => $topProducts,
            'salesByDay' => $salesByDay,
        ]);
    }
}
