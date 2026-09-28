<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Company\BranchController;
use App\Http\Controllers\Api\V1\Company\CompanyController;
use App\Http\Controllers\Api\V1\Company\WarehouseController;
use App\Http\Controllers\Api\V1\Company\WarehouseLocationController;
use App\Http\Controllers\Api\V1\Customer\CustomerController;
use App\Http\Controllers\Api\V1\Customer\CustomerGroupController;
use App\Http\Controllers\Api\V1\Customer\CustomerPaymentController;
use App\Http\Controllers\Api\V1\Customer\LoyaltyTransactionController;
use App\Http\Controllers\Api\V1\Employee\EmployeeAttendanceController;
use App\Http\Controllers\Api\V1\Employee\EmployeeCommissionController;
use App\Http\Controllers\Api\V1\Employee\EmployeeController;
use App\Http\Controllers\Api\V1\Employee\EmployeeShiftController;
use App\Http\Controllers\Api\V1\Finance\AccountingAccountController;
use App\Http\Controllers\Api\V1\Finance\BankAccountController;
use App\Http\Controllers\Api\V1\Finance\BankTransactionController;
use App\Http\Controllers\Api\V1\Finance\CashRegisterController;
use App\Http\Controllers\Api\V1\Finance\CashSessionController;
use App\Http\Controllers\Api\V1\Finance\ExpenseCategoryController;
use App\Http\Controllers\Api\V1\Finance\ExpenseController;
use App\Http\Controllers\Api\V1\Finance\JournalEntryController;
use App\Http\Controllers\Api\V1\Finance\PaymentController;
use App\Http\Controllers\Api\V1\Finance\PaymentMethodController;
use App\Http\Controllers\Api\V1\Inventory\StockAdjustmentController;
use App\Http\Controllers\Api\V1\Inventory\StockController;
use App\Http\Controllers\Api\V1\Inventory\StockMovementController;
use App\Http\Controllers\Api\V1\Inventory\StockTransferController;
use App\Http\Controllers\Api\V1\Notification\NotificationController;
use App\Http\Controllers\Api\V1\Product\BrandController;
use App\Http\Controllers\Api\V1\Product\CategoryController;
use App\Http\Controllers\Api\V1\Product\ProductBatchController;
use App\Http\Controllers\Api\V1\Product\ProductController;
use App\Http\Controllers\Api\V1\Product\ProductSerialController;
use App\Http\Controllers\Api\V1\Product\ProductVariantController;
use App\Http\Controllers\Api\V1\Product\UnitController;
use App\Http\Controllers\Api\V1\Promotion\CouponController;
use App\Http\Controllers\Api\V1\Promotion\PromotionController;
use App\Http\Controllers\Api\V1\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Api\V1\Purchasing\PurchaseReceiptController;
use App\Http\Controllers\Api\V1\Purchasing\PurchaseReturnController;
use App\Http\Controllers\Api\V1\Report\ReportController;
use App\Http\Controllers\Api\V1\Sales\InvoiceController;
use App\Http\Controllers\Api\V1\Sales\PosController;
use App\Http\Controllers\Api\V1\Sales\QuotationController;
use App\Http\Controllers\Api\V1\Sales\RefundController;
use App\Http\Controllers\Api\V1\Sales\SalesOrderController;
use App\Http\Controllers\Api\V1\Sales\SalesReturnController;
use App\Http\Controllers\Api\V1\Security\ApprovalRequestController;
use App\Http\Controllers\Api\V1\Security\AuditLogController;
use App\Http\Controllers\Api\V1\Security\PermissionController;
use App\Http\Controllers\Api\V1\Security\RoleController;
use App\Http\Controllers\Api\V1\Security\UserController;
use App\Http\Controllers\Api\V1\Setting\SettingController;
use App\Http\Controllers\Api\V1\Supplier\SupplierController;
use App\Http\Controllers\Api\V1\Supplier\SupplierPaymentController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    // Stateless deploy diagnostic: reports only whether variables are
    // present — never their values. No session, no cookies, no key needed.
    Route::get('/health', function () {
        $present = fn (string $key): bool => ($v = getenv($key)) !== false && $v !== '';

        $db = 'not-tested';
        try {
            DB::select('select 1');
            $db = 'ok';
        } catch (Throwable $e) {
            $db = class_basename($e).': '.substr($e->getMessage(), 0, 160);
        }

        return response()->json([
            'status' => 'ok',
            'env' => [
                'APP_KEY' => $present('APP_KEY'),
                'APP_URL' => $present('APP_URL'),
                'DB_HOST' => $present('DB_HOST'),
                'DB_DATABASE' => $present('DB_DATABASE'),
                'DB_USERNAME' => $present('DB_USERNAME'),
                'DB_PASSWORD' => $present('DB_PASSWORD'),
            ],
            'db' => $db,
        ]);
    })->name('health');

    // Auth routes (outside auth:sanctum to avoid CSRF issues)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    Route::middleware(['web', 'auth'])->group(function () {
        // Companies
        Route::apiResource('/companies', CompanyController::class);

        // Branches
        Route::apiResource('/branches', BranchController::class);

        // Warehouses
        Route::apiResource('/warehouses', WarehouseController::class);

        // Warehouse Locations
        Route::apiResource('/warehouse-locations', WarehouseLocationController::class);

        // Products
        Route::apiResource('/products', ProductController::class);
        Route::get('/products/{product}/stock', [ProductController::class, 'stock']);

        // Categories
        Route::apiResource('/categories', CategoryController::class);

        // Brands
        Route::apiResource('/brands', BrandController::class);

        // Units
        Route::apiResource('/units', UnitController::class);

        // Product Variants
        Route::apiResource('/product-variants', ProductVariantController::class);

        // Product Batches
        Route::apiResource('/product-batches', ProductBatchController::class);

        // Product Serials
        Route::apiResource('/product-serials', ProductSerialController::class);

        // Inventory - Stock
        Route::apiResource('/stock', StockController::class);
        Route::get('/stock/low-stock', [StockController::class, 'lowStock']);
        Route::get('/stock/expiring', [StockController::class, 'expiring']);

        // Inventory - Stock Movements
        Route::apiResource('/stock-movements', StockMovementController::class);

        // Inventory - Stock Adjustments
        Route::apiResource('/stock-adjustments', StockAdjustmentController::class);
        Route::post('/stock-adjustments/{stockAdjustment}/approve', [StockAdjustmentController::class, 'approve']);
        Route::post('/stock-adjustments/{stockAdjustment}/reject', [StockAdjustmentController::class, 'reject']);

        // Inventory - Stock Transfers
        Route::apiResource('/stock-transfers', StockTransferController::class);
        Route::post('/stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive']);

        // Sales - POS
        Route::get('/pos/products', [PosController::class, 'products']);
        Route::get('/pos/customers', [PosController::class, 'customers']);
        Route::post('/pos/checkout', [PosController::class, 'checkout']);
        Route::post('/pos/hold', [PosController::class, 'hold']);
        Route::get('/pos/held-orders', [PosController::class, 'heldOrders']);
        Route::post('/pos/resume/{salesOrder}', [PosController::class, 'resume']);

        // Sales - Sales Orders
        Route::apiResource('/sales-orders', SalesOrderController::class);

        // Sales - Quotations
        Route::apiResource('/quotations', QuotationController::class);
        Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convert']);

        // Sales - Invoices
        Route::apiResource('/invoices', InvoiceController::class);
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
        Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print']);

        // Sales - Returns
        Route::apiResource('/sales-returns', SalesReturnController::class);
        Route::post('/sales-returns/{salesReturn}/approve', [SalesReturnController::class, 'approve']);
        Route::post('/sales-returns/{salesReturn}/reject', [SalesReturnController::class, 'reject']);

        // Sales - Refunds
        Route::apiResource('/refunds', RefundController::class);
        Route::post('/refunds/{refund}/approve', [RefundController::class, 'approve']);
        Route::post('/refunds/{refund}/reject', [RefundController::class, 'reject']);

        // Purchasing - Purchase Orders
        Route::apiResource('/purchase-orders', PurchaseOrderController::class);
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve']);
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);

        // Purchasing - Purchase Receipts
        Route::apiResource('/purchase-receipts', PurchaseReceiptController::class);

        // Purchasing - Purchase Returns
        Route::apiResource('/purchase-returns', PurchaseReturnController::class);
        Route::post('/purchase-returns/{purchaseReturn}/approve', [PurchaseReturnController::class, 'approve']);
        Route::post('/purchase-returns/{purchaseReturn}/reject', [PurchaseReturnController::class, 'reject']);

        // Customers
        Route::apiResource('/customers', CustomerController::class);
        Route::get('/customers/{customer}/statement', [CustomerController::class, 'statement']);
        Route::get('/customers/{customer}/loyalty', [CustomerController::class, 'loyalty']);

        // Customer Groups
        Route::apiResource('/customer-groups', CustomerGroupController::class);

        // Customer Payments
        Route::apiResource('/customer-payments', CustomerPaymentController::class);

        // Loyalty Transactions
        Route::apiResource('/loyalty-transactions', LoyaltyTransactionController::class);

        // Suppliers
        Route::apiResource('/suppliers', SupplierController::class);
        Route::get('/suppliers/{supplier}/statement', [SupplierController::class, 'statement']);

        // Supplier Payments
        Route::apiResource('/supplier-payments', SupplierPaymentController::class);

        // Payments
        Route::apiResource('/payments', PaymentController::class);

        // Payment Methods
        Route::apiResource('/payment-methods', PaymentMethodController::class);

        // Bank Accounts
        Route::apiResource('/bank-accounts', BankAccountController::class);
        Route::post('/bank-accounts/{bankAccount}/reconcile', [BankAccountController::class, 'reconcile']);

        // Bank Transactions
        Route::apiResource('/bank-transactions', BankTransactionController::class);

        // Expenses
        Route::apiResource('/expenses', ExpenseController::class);
        Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve']);
        Route::post('/expenses/{expense}/reject', [ExpenseController::class, 'reject']);

        // Expense Categories
        Route::apiResource('/expense-categories', ExpenseCategoryController::class);

        // Cash Registers
        Route::apiResource('/cash-registers', CashRegisterController::class);

        // Cash Sessions
        Route::apiResource('/cash-sessions', CashSessionController::class);
        Route::post('/cash-sessions/{cashSession}/close', [CashSessionController::class, 'close']);
        Route::post('/cash-sessions/{cashSession}/reconcile', [CashSessionController::class, 'reconcile']);

        // Accounting
        Route::apiResource('/accounting-accounts', AccountingAccountController::class);
        Route::apiResource('/journal-entries', JournalEntryController::class);
        Route::post('/journal-entries/{journalEntry}/post', [JournalEntryController::class, 'post']);
        Route::post('/journal-entries/{journalEntry}/reverse', [JournalEntryController::class, 'reverse']);

        // Employees
        Route::apiResource('/employees', EmployeeController::class);

        // Employee Attendance
        Route::apiResource('/employee-attendance', EmployeeAttendanceController::class);

        // Employee Shifts
        Route::apiResource('/employee-shifts', EmployeeShiftController::class);

        // Employee Commissions
        Route::apiResource('/employee-commissions', EmployeeCommissionController::class);

        // Promotions
        Route::apiResource('/promotions', PromotionController::class);

        // Coupons
        Route::apiResource('/coupons', CouponController::class);

        // Security - Users
        Route::apiResource('/users', UserController::class);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);

        // Security - Roles
        Route::apiResource('/roles', RoleController::class);

        // Security - Permissions
        Route::apiResource('/permissions', PermissionController::class);

        // Security - Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);

        // Security - Approval Requests
        Route::apiResource('/approval-requests', ApprovalRequestController::class);
        Route::post('/approval-requests/{approvalRequest}/approve', [ApprovalRequestController::class, 'approve']);
        Route::post('/approval-requests/{approvalRequest}/reject', [ApprovalRequestController::class, 'reject']);

        // Notifications
        Route::apiResource('/notifications', NotificationController::class);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);

        // Reports
        Route::get('/reports/sales', [ReportController::class, 'sales']);
        Route::get('/reports/profit', [ReportController::class, 'profit']);
        Route::get('/reports/inventory', [ReportController::class, 'inventory']);
        Route::get('/reports/purchases', [ReportController::class, 'purchases']);
        Route::get('/reports/expenses', [ReportController::class, 'expenses']);
        Route::get('/reports/customers', [ReportController::class, 'customers']);
        Route::get('/reports/suppliers', [ReportController::class, 'suppliers']);
        Route::get('/reports/employees', [ReportController::class, 'employees']);
        Route::get('/reports/branches', [ReportController::class, 'branches']);
        Route::get('/reports/cash', [ReportController::class, 'cash']);
        Route::get('/reports/tax', [ReportController::class, 'tax']);

        // Settings
        Route::get('/settings', [SettingController::class, 'index']);
        Route::put('/settings', [SettingController::class, 'update']);
    });
});
