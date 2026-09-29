<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\Product\ProductController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::redirect('/login', '/dashboard')->name('login');

// Deploy probe: proves which code is live and whether API routes + DB work.
// Reports presence only — never secret values.
Route::get('/healthz', function () {
    $present = fn (string $key): bool => ($v = getenv($key)) !== false && $v !== '';

    $db = 'not-tested';
    try {
        DB::select('select 1');
        $db = 'ok';
    } catch (Throwable $e) {
        $db = (new ReflectionClass($e))->getShortName().': '.substr($e->getMessage(), 0, 160);
    }

    return response()->json([
        'probe' => 'healthz-v1',
        'routes_registered' => Route::getRoutes()->count(),
        'api_health_registered' => Route::has('api.health'),
        'login_store_registered' => Route::has('login.store'),
        'db' => $db,
        'bcrypt_rounds' => getenv('BCRYPT_ROUNDS'),
        'bcrypt_make' => (function () {
            try {
                return is_string(@password_hash('probe', PASSWORD_BCRYPT, ['cost' => 4])) ? 'ok' : 'failed-false';
            } catch (Throwable $e) {
                return 'failed-'.get_class($e);
            }
        })(),
        'env' => [
            'APP_KEY' => $present('APP_KEY'),
            'DB_HOST' => $present('DB_HOST'),
            'DB_DATABASE' => $present('DB_DATABASE'),
            'DB_USERNAME' => $present('DB_USERNAME'),
            'DB_PASSWORD' => $present('DB_PASSWORD'),
        ],
    ]);
})->name('healthz');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Inertia Frontend Routes
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/categories', fn () => inertia('categories/index'))->name('categories.index');
    Route::get('/brands', fn () => inertia('brands/index'))->name('brands.index');
    Route::get('/units', fn () => inertia('units/index'))->name('units.index');
    Route::get('/inventory/stock', [PageController::class, 'stocks'])->name('inventory.stock');
    Route::get('/stocks', [PageController::class, 'stocks'])->name('stocks.index');
    Route::get('/inventory/movements', fn () => inertia('inventory/movements'))->name('inventory.movements');
    Route::get('/inventory/adjustments', fn () => inertia('inventory/adjustments'))->name('inventory.adjustments');
    Route::get('/inventory/transfers', fn () => inertia('inventory/transfers'))->name('inventory.transfers');
    Route::get('/sales/orders', fn () => inertia('sales/orders'))->name('sales.orders.index');
    Route::get('/sales/quotations', fn () => inertia('sales/quotations'))->name('sales.quotations.index');
    Route::get('/sales/invoices', fn () => inertia('sales/invoices'))->name('sales.invoices.index');
    Route::get('/sales/refunds', fn () => inertia('sales/refunds'))->name('sales.refunds.index');
    Route::get('/purchasing/orders', fn () => inertia('purchasing/orders'))->name('purchasing.orders.index');
    Route::get('/purchasing/receiving', fn () => inertia('purchasing/receiving'))->name('purchasing.receiving.index');
    Route::get('/purchasing/returns', fn () => inertia('purchasing/returns'))->name('purchasing.returns.index');
    Route::get('/customers', [PageController::class, 'customers'])->name('customers.index');
    Route::get('/customers/create', fn () => inertia('customers/create'))->name('customers.create');
    Route::get('/customers/{customer}', fn () => inertia('customers/show'))->name('customers.show');
    Route::get('/customers/{customer}/statement', fn () => inertia('customers/statement'))->name('customers.statement');
    Route::get('/customer-groups', fn () => inertia('customer-groups/index'))->name('customer-groups.index');
    Route::get('/suppliers', [PageController::class, 'suppliers'])->name('suppliers.index');
    Route::post('/suppliers', [PageController::class, 'storeSupplier'])->name('suppliers.store');
    Route::get('/sales', [PageController::class, 'sales'])->name('sales.index');
    Route::get('/sales/{invoice}', [PageController::class, 'saleShow'])->name('sales.show');
    Route::get('/sales-returns', [PageController::class, 'salesReturns'])->name('sales.returns.index');
    Route::post('/sales-returns', [PageController::class, 'storeSalesReturn'])->name('sales.returns.store');
    Route::get('/purchases', [PageController::class, 'purchases'])->name('purchases.index');
    Route::get('/purchases/create', [PageController::class, 'purchaseCreate'])->name('purchases.create');
    Route::post('/purchases', [PageController::class, 'storePurchase'])->name('purchases.store');
    Route::get('/cash-sessions', [PageController::class, 'cashSessions'])->name('cash-sessions.index');
    Route::post('/cash-sessions/open', [PageController::class, 'openCashSession'])->name('cash-sessions.open');
    Route::post('/cash-sessions/{cashSession}/close', [PageController::class, 'closeCashSession'])->name('cash-sessions.close');
    Route::get('/users', [PageController::class, 'users'])->name('users.index');
    Route::post('/users', [PageController::class, 'storeUser'])->name('users.store');
    Route::get('/reports', [PageController::class, 'reports'])->name('reports.index');
    Route::get('/suppliers/create', fn () => inertia('suppliers/create'))->name('suppliers.create');
    Route::get('/suppliers/{supplier}', fn () => inertia('suppliers/show'))->name('suppliers.show');
    Route::get('/supplier-payments', fn () => inertia('supplier-payments/index'))->name('supplier-payments.index');
    Route::get('/finance/payments', fn () => inertia('finance/payments'))->name('finance.payments.index');
    Route::get('/finance/payment-methods', fn () => inertia('finance/payment-methods'))->name('finance.payment-methods.index');
    Route::get('/finance/bank-accounts', fn () => inertia('finance/bank-accounts'))->name('finance.bank-accounts.index');
    Route::get('/finance/bank-transactions', fn () => inertia('finance/bank-transactions'))->name('finance.bank-transactions.index');
    Route::get('/finance/expenses', fn () => inertia('finance/expenses'))->name('finance.expenses.index');
    Route::get('/finance/expense-categories', fn () => inertia('finance/expense-categories'))->name('finance.expense-categories.index');
    Route::get('/finance/cash-registers', fn () => inertia('finance/cash-registers'))->name('finance.cash-registers.index');
    Route::get('/finance/cash-sessions', fn () => inertia('finance/cash-sessions'))->name('finance.cash-sessions.index');
    Route::get('/finance/accounting', fn () => inertia('finance/accounting'))->name('finance.accounting.index');
    Route::get('/finance/journal-entries', fn () => inertia('finance/journal-entries'))->name('finance.journal-entries.index');
    Route::get('/employees', fn () => inertia('employees/index'))->name('employees.index');
    Route::get('/employees/create', fn () => inertia('employees/create'))->name('employees.create');
    Route::get('/employees/attendance', fn () => inertia('employees/attendance'))->name('employees.attendance.index');
    Route::get('/employees/shifts', fn () => inertia('employees/shifts'))->name('employees.shifts.index');
    Route::get('/employees/commissions', fn () => inertia('employees/commissions'))->name('employees.commissions.index');
    Route::get('/promotions', fn () => inertia('promotions/index'))->name('promotions.index');
    Route::get('/coupons', fn () => inertia('coupons/index'))->name('coupons.index');
    Route::get('/security/users', fn () => inertia('security/users'))->name('security.users.index');
    Route::get('/security/roles', fn () => inertia('security/roles'))->name('security.roles.index');
    Route::get('/security/permissions', fn () => inertia('security/permissions'))->name('security.permissions.index');
    Route::get('/security/audit-logs', fn () => inertia('security/audit-logs'))->name('security.audit-logs.index');
    Route::get('/security/approvals', fn () => inertia('security/approvals'))->name('security.approvals.index');
    Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/read-all', [PageController::class, 'markNotificationsRead'])->name('notifications.readAll');
    Route::get('/reports/sales', fn () => inertia('reports/sales'))->name('reports.sales');
    Route::get('/reports/profit', fn () => inertia('reports/profit'))->name('reports.profit');
    Route::get('/reports/inventory', fn () => inertia('reports/inventory'))->name('reports.inventory');
    Route::get('/reports/purchases', fn () => inertia('reports/purchases'))->name('reports.purchases');
    Route::get('/reports/expenses', fn () => inertia('reports/expenses'))->name('reports.expenses');
    Route::get('/reports/customers', fn () => inertia('reports/customers'))->name('reports.customers');
    Route::get('/reports/suppliers', fn () => inertia('reports/suppliers'))->name('reports.suppliers');
    Route::get('/reports/employees', fn () => inertia('reports/employees'))->name('reports.employees');
    Route::get('/reports/branches', fn () => inertia('reports/branches'))->name('reports.branches');
    Route::get('/reports/cash', fn () => inertia('reports/cash'))->name('reports.cash');
    Route::get('/reports/tax', fn () => inertia('reports/tax'))->name('reports.tax');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings.index');
    Route::get('/companies', fn () => inertia('companies/index'))->name('companies.index');
    Route::get('/branches', fn () => inertia('branches/index'))->name('branches.index');
    Route::get('/warehouses', fn () => inertia('warehouses/index'))->name('warehouses.index');

    // POS Actions
    Route::post('/pos/checkout', [App\Http\Controllers\Api\V1\Sales\PosController::class, 'checkout'])->name('pos.store');
    Route::post('/pos/hold', [App\Http\Controllers\Api\V1\Sales\PosController::class, 'hold'])->name('pos.hold');
    Route::post('/pos/resume/{salesOrder}', [App\Http\Controllers\Api\V1\Sales\PosController::class, 'resume'])->name('pos.resume');
});
