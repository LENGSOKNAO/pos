<?php

namespace App\Http\Controllers\Api\V1\Report;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends BaseApiController
{
    public function sales(Request $request)
    {
        $query = Invoice::where('status', 'paid');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        $totalSales = $query->sum('total');
        $totalOrders = $query->count();
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        $dailySales = $query->select(DB::raw('DATE(invoice_date) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $byBranch = $query->select('branch_id', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as orders'))
            ->with('branch')
            ->groupBy('branch_id')
            ->get();

        $byPaymentMethod = Payment::whereHas('invoice', fn ($q) => $q->where('status', 'paid'))
            ->select('payment_method_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->with('paymentMethod')
            ->groupBy('payment_method_id')
            ->get();

        $topProducts = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.status', 'paid')
            ->when($request->has('branch_id'), fn ($q) => $q->where('invoices.branch_id', $request->branch_id))
            ->when($request->has('date_from'), fn ($q) => $q->where('invoices.invoice_date', '>=', $request->date_from))
            ->when($request->has('date_to'), fn ($q) => $q->where('invoices.invoice_date', '<=', $request->date_to))
            ->select('products.id', 'products.name', 'products.sku', DB::raw('SUM(invoice_items.quantity) as total_qty'), DB::raw('SUM(invoice_items.total) as total_sales'))
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        return $this->success([
            'summary' => [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'avg_order_value' => $avgOrderValue,
            ],
            'daily_sales' => $dailySales,
            'by_branch' => $byBranch,
            'by_payment_method' => $byPaymentMethod,
            'top_products' => $topProducts,
        ]);
    }

    public function profit(Request $request)
    {
        $query = Invoice::where('status', 'paid');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        $invoices = $query->with('items')->get();

        $totalRevenue = $invoices->sum('total');
        $totalCost = $invoices->sum(function ($inv) {
            return $inv->items->sum(fn ($item) => $item->cost_price * $item->quantity);
        });
        $totalDiscount = $invoices->sum('discount');
        $totalTax = $invoices->sum('tax');
        $grossProfit = $totalRevenue - $totalCost;
        $netProfit = $grossProfit - $totalDiscount - $totalTax;

        $dailyProfit = $invoices->groupBy(fn ($inv) => $inv->invoice_date->format('Y-m-d'))
            ->map(fn ($group) => [
                'date' => $group->first()->invoice_date->format('Y-m-d'),
                'revenue' => $group->sum('total'),
                'cost' => $group->sum(fn ($inv) => $inv->items->sum(fn ($item) => $item->cost_price * $item->quantity)),
                'profit' => $group->sum('total') - $group->sum(fn ($inv) => $inv->items->sum(fn ($item) => $item->cost_price * $item->quantity)),
            ])
            ->values();

        return $this->success([
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'profit_margin' => $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0,
            ],
            'daily_profit' => $dailyProfit,
        ]);
    }

    public function inventory(Request $request)
    {
        $query = Stock::with('product', 'warehouse', 'location');

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $totalItems = $query->count();
        $totalQuantity = $query->sum('quantity');
        $totalValue = $query->sum(DB::raw('quantity * average_cost'));
        $lowStock = $query->whereRaw('quantity - reserved_quantity <= products.reorder_level')
            ->join('products', 'stock.product_id', '=', 'products.id')
            ->count();
        $outOfStock = $query->where('quantity', 0)->count();

        $byCategory = Product::with('category')
            ->whereHas('stock')
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->with('category')
            ->get();

        $byWarehouse = Stock::select('warehouse_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(quantity * average_cost) as total_value'))
            ->groupBy('warehouse_id')
            ->with('warehouse')
            ->get();

        return $this->success([
            'summary' => [
                'total_items' => $totalItems,
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'low_stock_count' => $lowStock,
                'out_of_stock_count' => $outOfStock,
            ],
            'by_category' => $byCategory,
            'by_warehouse' => $byWarehouse,
        ]);
    }

    public function purchases(Request $request)
    {
        $query = PurchaseOrder::whereIn('status', ['received', 'partial_received']);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        $totalPurchases = $query->sum('total');
        $totalOrders = $query->count();

        $bySupplier = $query->select('supplier_id', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as orders'))
            ->with('supplier')
            ->groupBy('supplier_id')
            ->get();

        return $this->success([
            'summary' => [
                'total_purchases' => $totalPurchases,
                'total_orders' => $totalOrders,
            ],
            'by_supplier' => $bySupplier,
        ]);
    }

    public function expenses(Request $request)
    {
        $query = Expense::whereIn('status', ['approved', 'paid']);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('date_from')) {
            $query->where('expense_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('expense_date', '<=', $request->date_to);
        }

        $totalExpenses = $query->sum('amount');
        $totalCount = $query->count();

        $byCategory = $query->select('category_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->with('category')
            ->groupBy('category_id')
            ->get();

        $byBranch = $query->select('branch_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->with('branch')
            ->groupBy('branch_id')
            ->get();

        return $this->success([
            'summary' => [
                'total_expenses' => $totalExpenses,
                'total_count' => $totalCount,
            ],
            'by_category' => $byCategory,
            'by_branch' => $byBranch,
        ]);
    }

    public function customers(Request $request)
    {
        $query = Customer::where('status', 'active');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $totalCustomers = $query->count();
        $totalSpent = $query->with('invoices')->get()->sum(fn ($c) => $c->invoices->where('status', 'paid')->sum('total'));
        $totalDebt = $query->sum('due_amount');

        $topCustomers = $query->with('invoices')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'total_spent' => $c->invoices->where('status', 'paid')->sum('total'),
                'outstanding' => $c->due_amount,
            ])
            ->sortByDesc('total_spent')
            ->take(10)
            ->values();

        return $this->success([
            'summary' => [
                'total_customers' => $totalCustomers,
                'total_spent' => $totalSpent,
                'total_debt' => $totalDebt,
            ],
            'top_customers' => $topCustomers,
        ]);
    }

    public function suppliers(Request $request)
    {
        $query = Supplier::where('status', 'active');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $totalSuppliers = $query->count();
        $totalPurchases = $query->with('purchaseOrders')->get()->sum(fn ($s) => $s->purchaseOrders->whereIn('status', ['received', 'partial_received'])->sum('total'));
        $totalDebt = $query->sum('due_amount');

        return $this->success([
            'summary' => [
                'total_suppliers' => $totalSuppliers,
                'total_purchases' => $totalPurchases,
                'total_debt' => $totalDebt,
            ],
        ]);
    }

    public function employees(Request $request)
    {
        $query = Employee::where('status', 'active');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $totalEmployees = $query->count();

        $salesPerformance = $query->with(['invoices', 'commissions'])
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->first_name.' '.$e->last_name,
                'total_sales' => $e->invoices->where('status', 'paid')->sum('total'),
                'commission_earned' => $e->commissions->sum('commission_amount'),
            ])
            ->sortByDesc('total_sales')
            ->values();

        return $this->success([
            'summary' => [
                'total_employees' => $totalEmployees,
            ],
            'sales_performance' => $salesPerformance,
        ]);
    }

    public function branches(Request $request)
    {
        $query = Branch::where('status', 'active');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $branchData = $query->with(['salesOrders', 'invoices', 'expenses', 'employees'])
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'total_sales' => $b->invoices->where('status', 'paid')->sum('total'),
                'total_expenses' => $b->expenses->whereIn('status', ['approved', 'paid'])->sum('amount'),
                'profit' => $b->invoices->where('status', 'paid')->sum('total') - $b->expenses->whereIn('status', ['approved', 'paid'])->sum('amount'),
                'employee_count' => $b->employees->where('status', 'active')->count(),
            ])
            ->values();

        return $this->success($branchData);
    }

    public function cash(Request $request)
    {
        $query = CashSession::where('status', 'closed');

        if ($request->has('branch_id')) {
            $query->whereHas('register', fn ($q) => $q->where('branch_id', $request->branch_id));
        }

        if ($request->has('date_from')) {
            $query->where('opened_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('opened_at', '<=', $request->date_to);
        }

        $sessions = $query->with('register', 'employee')->get();

        $totalOpening = $sessions->sum('opening_cash');
        $totalClosing = $sessions->sum('closing_cash');
        $totalExpected = $sessions->sum('expected_cash');
        $totalDifference = $sessions->sum('difference');

        return $this->success([
            'summary' => [
                'total_sessions' => $sessions->count(),
                'total_opening_cash' => $totalOpening,
                'total_closing_cash' => $totalClosing,
                'total_expected_cash' => $totalExpected,
                'total_difference' => $totalDifference,
            ],
            'sessions' => $sessions->map(fn ($s) => [
                'id' => $s->id,
                'register' => $s->register->name,
                'employee' => $s->employee->first_name.' '.$s->employee->last_name,
                'opening_cash' => $s->opening_cash,
                'closing_cash' => $s->closing_cash,
                'expected_cash' => $s->expected_cash,
                'difference' => $s->difference,
                'opened_at' => $s->opened_at,
                'closed_at' => $s->closed_at,
            ]),
        ]);
    }

    public function tax(Request $request)
    {
        $query = Invoice::where('status', 'paid');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        $totalTax = $query->sum('tax');
        $totalSales = $query->sum('total');

        $dailyTax = $query->select(DB::raw('DATE(invoice_date) as date'), DB::raw('SUM(tax) as total_tax'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->success([
            'summary' => [
                'total_tax' => $totalTax,
                'total_sales' => $totalSales,
                'tax_rate' => $totalSales > 0 ? ($totalTax / $totalSales) * 100 : 0,
            ],
            'daily_tax' => $dailyTax,
        ]);
    }
}
