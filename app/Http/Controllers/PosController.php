<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        $employee = $user->employee;
        $companyId = $employee?->company_id;
        $branchId = $employee?->branch_id;

        // Get products for this company
        $productsQuery = Product::with(['category', 'brand', 'unit'])
            ->withSum('stock as stock_quantity', 'quantity')
            ->withSum('stock as stock_reserved', 'reserved_quantity')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name');

        // Get categories
        $categories = Category::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get customers
        $customers = Customer::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get payment methods
        $paymentMethods = PaymentMethod::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get open cash session for this employee
        $cashSession = CashSession::where('employee_id', $employee?->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        return Inertia::render('pos/index', [
            'products' => Inertia::defer(fn () => $productsQuery->paginate(50)),
            'categories' => $categories,
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
            'cashSession' => $cashSession,
        ]);
    }
}
