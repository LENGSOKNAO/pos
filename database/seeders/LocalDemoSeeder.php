<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Company;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerPayment;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeCommission;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LoyaltyTransaction;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Refund;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::count() > 0) {
            $this->command->info('Demo data already present, skipping.');

            return;
        }

        $company = Company::firstOrFail();
        $branch = Branch::firstOrFail();
        $warehouse = Warehouse::firstOrFail();
        $companyId = $company->id;
        $branchId = $branch->id;
        $warehouseId = $warehouse->id;
        $user = User::first();
        $employee = Employee::first();
        $employeeId = $employee?->id;
        $methods = PaymentMethod::orderBy('name')->get();
        $cashMethod = $methods->firstWhere('code', 'cash') ?? $methods->first();

        $uuid = fn (): string => (string) Str::uuid();

        // Units, brands, categories
        $units = [];
        foreach ([['Piece', 'pc'], ['Kilogram', 'kg'], ['Liter', 'L'], ['Box', 'box']] as [$name, $symbol]) {
            $units[$symbol] = Unit::create(['id' => $uuid(), 'company_id' => $companyId, 'name' => $name, 'symbol' => $symbol]);
        }
        $brands = [];
        foreach ([['Coca Cola', 'COKE'], ['Pepsi', 'PEPSI'], ['ABC Trading', 'ABC'], ['Local Fresh', 'LOCF']] as [$name, $code]) {
            $brands[$name] = Brand::create(['id' => $uuid(), 'company_id' => $companyId, 'name' => $name, 'code' => $code, 'status' => 'active']);
        }
        $cats = [];
        foreach ([['Beverages', 'BEV'], ['Snacks', 'SNK'], ['Household', 'HSH'], ['Dairy', 'DRY']] as [$name, $code]) {
            $cats[$name] = Category::create(['id' => $uuid(), 'company_id' => $companyId, 'name' => $name, 'code' => $code, 'status' => 'active']);
        }

        // Second warehouse for transfers
        $warehouse2 = Warehouse::create(['id' => $uuid(), 'branch_id' => $branchId, 'code' => 'WH-02', 'name' => 'Branch 2 Warehouse', 'status' => 'active']);

        // Products
        $items = [
            ['Coca Cola 330ml', 'COKE-330', 'Beverages', 'Coca Cola', 'pc', 0.55, 1.00, 1.00, 150],
            ['Pepsi 330ml', 'PEP-330', 'Beverages', 'Pepsi', 'pc', 0.55, 1.00, 1.00, 140],
            ['Drinking Water 500ml', 'WTR-500', 'Beverages', 'Local Fresh', 'pc', 0.20, 0.50, 0.50, 300],
            ['Iced Coffee 250ml', 'COF-250', 'Beverages', 'ABC Trading', 'pc', 0.80, 1.75, 1.75, 90],
            ['Fresh Milk 1L', 'MLK-1L', 'Dairy', 'Local Fresh', 'L', 1.10, 2.25, 2.25, 60],
            ['Potato Chips 150g', 'CHP-150', 'Snacks', 'ABC Trading', 'pc', 0.90, 2.00, 2.00, 120],
            ['Chocolate Bar 100g', 'CHC-100', 'Snacks', 'ABC Trading', 'pc', 0.70, 1.80, 1.80, 8],
            ['Rice 5kg', 'RCE-5K', 'Snacks', 'Local Fresh', 'kg', 3.20, 5.50, 5.00, 70],
            ['Dish Soap 750ml', 'DSH-750', 'Household', 'ABC Trading', 'pc', 1.00, 2.50, 2.50, 85],
            ['Laundry Powder 2kg', 'LND-2K', 'Household', 'ABC Trading', 'kg', 2.40, 4.80, 4.50, 40],
            ['Paper Towels 4pk', 'PTW-4P', 'Household', 'Local Fresh', 'pc', 1.80, 3.80, 3.80, 0],
            ['Green Tea 25pk', 'GTE-25', 'Beverages', 'Pepsi', 'pc', 1.20, 2.90, 2.90, 65],
        ];
        $products = [];
        foreach ($items as [$name, $sku, $cat, $brand, $unit, $cost, $price, $whole, $qty]) {
            $p = Product::create([
                'id' => $uuid(), 'company_id' => $companyId, 'category_id' => $cats[$cat]->id,
                'brand_id' => $brands[$brand]->id, 'unit_id' => $units[$unit]->id,
                'name' => $name, 'sku' => $sku, 'barcode' => 'BC'.$sku,
                'cost_price' => $cost, 'selling_price' => $price, 'wholesale_price' => $whole,
                'reorder_level' => 10, 'status' => 'active',
            ]);
            DB::table('stock')->insert(['id' => $uuid(), 'product_id' => $p->id,
                'warehouse_id' => $warehouseId, 'quantity' => $qty, 'reserved_quantity' => 0,
                'damaged_quantity' => 0, 'average_cost' => $cost]);
            DB::table('stock_movements')->insert(['id' => $uuid(), 'product_id' => $p->id,
                'warehouse_id' => $warehouseId, 'movement_type' => 'purchase', 'reference_type' => 'opening',
                'reference_id' => $p->id, 'quantity' => $qty, 'unit_cost' => $cost, 'balance_after' => $qty,
                'employee_id' => $employeeId, 'created_at' => now()]);
            ProductVariant::create(['id' => $uuid(), 'product_id' => $p->id, 'sku' => $sku.'-V1',
                'barcode' => 'BC'.$sku.'V1', 'variant_name' => 'Standard', 'cost_price' => $cost,
                'selling_price' => $price, 'status' => 'active']);
            ProductBatch::create(['id' => $uuid(), 'product_id' => $p->id, 'warehouse_id' => $warehouseId,
                'batch_number' => 'B-'.$sku, 'expiry_date' => now()->addYear(), 'cost_price' => $cost,
                'quantity' => $qty, 'status' => 'active']);
            $products[] = $p;
        }

        // Customer groups + customers
        $retail = CustomerGroup::create(['id' => $uuid(), 'company_id' => $companyId, 'name' => 'Retail', 'discount_rate' => 0, 'status' => 'active']);
        $wholesale = CustomerGroup::create(['id' => $uuid(), 'company_id' => $companyId, 'name' => 'Wholesale', 'discount_rate' => 5, 'status' => 'active']);
        $customers = [];
        foreach ([
            ['WALK-IN', 'Walk-in Customer', null, 'Retail', 0],
            ['CUST-001', 'Sokha Mey', '012 345 678', 'Retail', 500],
            ['CUST-002', 'Dara Chan', '098 765 432', 'Retail', 300],
            ['CUST-003', 'City Mart', '023 456 789', 'Wholesale', 2000],
            ['CUST-004', 'Green Grocers', '015 222 333', 'Wholesale', 1500],
        ] as [$code, $name, $phone, $group, $limit]) {
            $customers[] = Customer::create(['id' => $uuid(), 'company_id' => $companyId, 'customer_code' => $code,
                'name' => $name, 'phone' => $phone, 'customer_group_id' => ($group === 'Retail' ? $retail : $wholesale)->id,
                'credit_limit' => $limit, 'credit_days' => 30, 'loyalty_points' => rand(0, 80), 'status' => 'active']);
        }

        // Suppliers
        $suppliers = [];
        foreach ([['SUP-001', 'ABC Trading Co.', '023 111 222'], ['SUP-002', 'Cambodia Distribution', '023 333 444'], ['SUP-003', 'Local Fresh Supplier', '012 999 888']] as [$code, $name, $phone]) {
            $suppliers[] = Supplier::create(['id' => $uuid(), 'company_id' => $companyId, 'supplier_code' => $code, 'name' => $name, 'phone' => $phone, 'status' => 'active']);
        }

        // Employees + attendance
        $staff = [];
        foreach ([['Sokha', 'Mey', 'Cashier'], ['Dara', 'Chan', 'Store Manager']] as $i => [$fn, $ln, $pos]) {
            $e = Employee::create(['id' => $uuid(), 'company_id' => $companyId, 'branch_id' => $branchId,
                'employee_code' => 'EMP-10'.($i + 1), 'first_name' => $fn, 'last_name' => $ln,
                'position' => $pos, 'hire_date' => now()->subYear(), 'salary' => 300 + $i * 200,
                'commission_rate' => 2, 'status' => 'active']);
            $staff[] = $e;
            EmployeeAttendance::create(['id' => $uuid(), 'employee_id' => $e->id, 'branch_id' => $branchId,
                'date' => now()->toDateString(), 'clock_in' => now()->setTime(8, 0), 'clock_out' => now()->setTime(17, 0), 'status' => 'present']);
        }

        // Cash registers + sessions
        $register = CashRegister::first() ?? CashRegister::create(['id' => $uuid(), 'branch_id' => $branchId, 'name' => 'Front Register', 'terminal_number' => 'T-01', 'status' => 'active']);
        $session = CashSession::create(['id' => $uuid(), 'register_id' => $register->id, 'employee_id' => $employeeId,
            'opening_cash' => 100, 'expected_cash' => 100, 'status' => 'open', 'opened_at' => now()->startOfDay()]);

        // Purchase: order -> partial receive -> stock up
        $po = PurchaseOrder::create(['id' => $uuid(), 'company_id' => $companyId, 'branch_id' => $branchId,
            'warehouse_id' => $warehouseId, 'supplier_id' => $suppliers[0]->id, 'order_number' => 'PO-'.now()->format('Ymd').'-001',
            'order_date' => now()->subDays(5), 'expected_date' => now()->addDays(2), 'subtotal' => 0, 'discount' => 0, 'tax' => 0,
            'total' => 0, 'status' => 'approved', 'created_by' => $user?->id, 'approved_by' => $user?->id]);
        $poTotal = 0;
        foreach (array_slice($products, 0, 4) as $i => $p) {
            $qty = 50 + $i * 10;
            $line = $qty * (float) $p->cost_price;
            $poTotal += $line;
            PurchaseOrderItem::create(['id' => $uuid(), 'purchase_order_id' => $po->id, 'product_id' => $p->id,
                'quantity' => $qty, 'received_quantity' => $qty, 'unit_cost' => $p->cost_price, 'discount' => 0, 'tax' => 0, 'total' => $line]);
        }
        $po->update(['subtotal' => $poTotal, 'total' => $poTotal]);
        $receipt = PurchaseReceipt::create(['id' => $uuid(), 'purchase_order_id' => $po->id, 'warehouse_id' => $warehouseId,
            'receipt_number' => 'RCV-'.now()->format('Ymd').'-001', 'received_by' => $user?->id, 'received_at' => now()->subDays(3), 'status' => 'completed']);
        foreach ($po->items as $poi) {
            PurchaseReceiptItem::create(['id' => $uuid(), 'receipt_id' => $receipt->id, 'product_id' => $poi->product_id,
                'quantity' => $poi->quantity, 'unit_cost' => $poi->unit_cost]);
            $stock = Stock::where('product_id', $poi->product_id)->where('warehouse_id', $warehouseId)->first();
            $before = (float) $stock->quantity;
            $stock->increment('quantity', $poi->quantity);
            DB::table('stock_movements')->insert(['id' => $uuid(), 'product_id' => $poi->product_id,
                'warehouse_id' => $warehouseId, 'movement_type' => 'purchase', 'reference_type' => 'purchase_receipt',
                'reference_id' => $receipt->id, 'quantity' => $poi->quantity, 'unit_cost' => $poi->unit_cost,
                'balance_after' => $before + (float) $poi->quantity, 'employee_id' => $employeeId, 'created_at' => now()]);
        }
        SupplierPayment::create(['id' => $uuid(), 'supplier_id' => $suppliers[0]->id, 'amount' => round($poTotal * 0.6, 2),
            'payment_method_id' => $cashMethod?->id, 'payment_date' => now()->subDays(2), 'reference_number' => 'SPAY-001', 'paid_by' => $user?->id]);

        // Sales: 5 invoices over last 5 days (one partial/credit)
        $invoices = [];
        foreach ([4, 3, 2, 1, 0] as $d) {
            $date = now()->subDays($d)->setTime(10 + $d, 15);
            $pick = array_slice($products, $d % 8, 3);
            $subtotal = 0;
            foreach ($pick as $p) {
                $subtotal += 2 * (float) $p->selling_price;
            }
            $isCredit = $d === 1;
            $paid = $isCredit ? round($subtotal / 2, 2) : $subtotal;
            $order = SalesOrder::create(['id' => $uuid(), 'company_id' => $companyId, 'branch_id' => $branchId,
                'customer_id' => $customers[($d + 1) % count($customers)]->id, 'employee_id' => $employeeId,
                'order_number' => 'SO-'.$date->format('Ymd').'-00'.$d, 'order_type' => 'pos', 'order_date' => $date,
                'subtotal' => $subtotal, 'discount' => 0, 'tax' => 0, 'total' => $subtotal, 'status' => 'completed']);
            $inv = Invoice::create(['id' => $uuid(), 'branch_id' => $branchId, 'customer_id' => $order->customer_id,
                'sales_order_id' => $order->id, 'invoice_number' => 'INV-'.$date->format('Ymd').'-00'.$d,
                'invoice_date' => $date, 'subtotal' => $subtotal, 'discount' => 0, 'tax' => 0, 'total' => $subtotal,
                'paid_amount' => $paid, 'due_amount' => $subtotal - $paid, 'status' => $isCredit ? 'partial' : 'paid',
                'created_at' => $date, 'updated_at' => $date]);
            foreach ($pick as $p) {
                SalesOrderItem::create(['id' => $uuid(), 'sales_order_id' => $order->id, 'product_id' => $p->id,
                    'quantity' => 2, 'unit_price' => $p->selling_price, 'discount' => 0, 'tax' => 0,
                    'cost_price' => $p->cost_price, 'total' => 2 * (float) $p->selling_price]);
                InvoiceItem::create(['id' => $uuid(), 'invoice_id' => $inv->id, 'product_id' => $p->id,
                    'quantity' => 2, 'unit_price' => $p->selling_price, 'discount' => 0, 'tax' => 0,
                    'cost_price' => $p->cost_price, 'total' => 2 * (float) $p->selling_price]);
                $stock = Stock::where('product_id', $p->id)->where('warehouse_id', $warehouseId)->first();
                if ($stock && (float) $stock->quantity >= 2) {
                    $before = (float) $stock->quantity;
                    $stock->decrement('quantity', 2);
                    DB::table('stock_movements')->insert(['id' => $uuid(), 'product_id' => $p->id,
                        'warehouse_id' => $warehouseId, 'movement_type' => 'sale', 'reference_type' => 'sales_order',
                        'reference_id' => $order->id, 'quantity' => -2, 'unit_cost' => $p->cost_price,
                        'balance_after' => $before - 2, 'employee_id' => $employeeId, 'created_at' => $date]);
                }
            }
            Payment::create(['id' => $uuid(), 'company_id' => $companyId, 'customer_id' => $inv->customer_id,
                'invoice_id' => $inv->id, 'payment_method_id' => $cashMethod?->id, 'amount' => $paid,
                'status' => 'completed', 'payment_date' => $date, 'received_by' => $user?->id]);
            CustomerPayment::create(['id' => $uuid(), 'customer_id' => $inv->customer_id, 'invoice_id' => $inv->id,
                'amount' => $paid, 'payment_method_id' => $cashMethod?->id, 'payment_date' => $date, 'received_by' => $user?->id]);
            LoyaltyTransaction::create(['id' => $uuid(), 'customer_id' => $inv->customer_id, 'invoice_id' => $inv->id,
                'points' => (int) floor($subtotal / 10), 'transaction_type' => 'earn']);
            if ($employee) {
                EmployeeCommission::create(['id' => $uuid(), 'employee_id' => $employee->id, 'invoice_id' => $inv->id,
                    'commission_rate' => 2, 'commission_amount' => round($subtotal * 0.02, 2)]);
            }
            $invoices[] = $inv;
        }

        // Quotation
        $q = Quotation::create(['id' => $uuid(), 'company_id' => $companyId, 'branch_id' => $branchId,
            'customer_id' => $customers[3]->id, 'quotation_number' => 'QT-'.now()->format('Ymd').'-001',
            'quotation_date' => now(), 'expiry_date' => now()->addDays(15), 'subtotal' => 0, 'discount' => 0,
            'tax' => 0, 'total' => 0, 'status' => 'sent', 'created_by' => $user?->id]);
        $qt = 0;
        foreach (array_slice($products, 0, 2) as $p) {
            $line = 5 * (float) $p->selling_price;
            $qt += $line;
            QuotationItem::create(['id' => $uuid(), 'quotation_id' => $q->id, 'product_id' => $p->id,
                'quantity' => 5, 'unit_price' => $p->selling_price, 'discount' => 0, 'tax' => 0, 'total' => $line]);
        }
        $q->update(['subtotal' => $qt, 'total' => $qt]);

        // Return + refund on first invoice
        $firstInv = $invoices[0];
        $firstItem = $firstInv->items()->first();
        $ret = SalesReturn::create(['id' => $uuid(), 'invoice_id' => $firstInv->id, 'customer_id' => $firstInv->customer_id,
            'branch_id' => $branchId, 'return_number' => 'RET-'.now()->format('Ymd').'-001', 'reason' => 'Damaged item',
            'refund_amount' => (float) $firstItem->unit_price, 'status' => 'completed',
            'created_by' => $user?->id, 'approved_by' => $user?->id]);
        SalesReturnItem::create(['id' => $uuid(), 'sales_return_id' => $ret->id, 'product_id' => $firstItem->product_id,
            'quantity' => 1, 'unit_price' => $firstItem->unit_price, 'refund_amount' => (float) $firstItem->unit_price]);
        Refund::create(['id' => $uuid(), 'sales_return_id' => $ret->id, 'payment_method_id' => $cashMethod?->id,
            'amount' => (float) $firstItem->unit_price, 'refunded_by' => $user?->id, 'refunded_at' => now(), 'status' => 'completed']);

        // Stock adjustment + transfer
        $adj = StockAdjustment::create(['id' => $uuid(), 'warehouse_id' => $warehouseId,
            'adjustment_number' => 'ADJ-'.now()->format('Ymd').'-001', 'reason' => 'Cycle count correction',
            'status' => 'approved', 'created_by' => $user?->id, 'approved_by' => $user?->id]);
        $adjProduct = $products[6];
        $adjStock = Stock::where('product_id', $adjProduct->id)->where('warehouse_id', $warehouseId)->first();
        StockAdjustmentItem::create(['id' => $uuid(), 'adjustment_id' => $adj->id, 'product_id' => $adjProduct->id,
            'quantity_before' => (float) $adjStock->quantity, 'quantity_after' => (float) $adjStock->quantity + 5,
            'difference' => 5, 'reason' => 'Found stock']);
        $transfer = StockTransfer::create(['id' => $uuid(), 'from_warehouse_id' => $warehouseId,
            'to_warehouse_id' => $warehouse2->id, 'transfer_number' => 'TRF-'.now()->format('Ymd').'-001',
            'status' => 'completed', 'created_by' => $user?->id, 'approved_by' => $user?->id, 'transferred_at' => now()]);
        StockTransferItem::create(['id' => $uuid(), 'transfer_id' => $transfer->id, 'product_id' => $products[0]->id, 'quantity' => 10]);

        // Expenses
        $rent = ExpenseCategory::create(['id' => $uuid(), 'company_id' => $companyId, 'name' => 'Rent', 'description' => 'Shop rent']);
        $util = ExpenseCategory::create(['id' => $uuid(), 'company_id' => $companyId, 'name' => 'Utilities', 'description' => 'Electricity & water']);
        foreach ([['Shop rent September', 800, $rent->id, 6], ['Electricity bill', 95.50, $util->id, 2], ['Water bill', 28.75, $util->id, 1]] as [$desc, $amt, $cat, $ago]) {
            Expense::create(['id' => $uuid(), 'company_id' => $companyId, 'branch_id' => $branchId, 'category_id' => $cat,
                'employee_id' => $employeeId, 'expense_number' => 'EXP-'.now()->subDays($ago)->format('Ymd').'-0'.$ago,
                'description' => $desc, 'amount' => $amt, 'payment_method_id' => $cashMethod?->id,
                'expense_date' => now()->subDays($ago), 'status' => 'approved', 'created_by' => $user?->id, 'approved_by' => $user?->id]);
        }

        // Bank accounts + transactions
        $aba = BankAccount::create(['id' => $uuid(), 'company_id' => $companyId, 'branch_id' => $branchId,
            'bank_name' => 'ABA', 'account_name' => 'Main Business', 'account_number' => '000123456',
            'currency' => 'USD', 'opening_balance' => 5000, 'current_balance' => 6200, 'status' => 'active']);
        $acleda = BankAccount::create(['id' => $uuid(), 'company_id' => $companyId, 'branch_id' => $branchId,
            'bank_name' => 'ACLEDA', 'account_name' => 'Savings', 'account_number' => '000987654',
            'currency' => 'USD', 'opening_balance' => 2000, 'current_balance' => 2000, 'status' => 'active']);
        BankTransaction::create(['id' => $uuid(), 'bank_account_id' => $aba->id, 'transaction_type' => 'deposit',
            'amount' => 1200, 'balance_after' => 6200, 'transaction_date' => now()->subDays(2), 'description' => 'Daily cash deposit']);

        // Promotion + coupons
        $promo = Promotion::create(['id' => $uuid(), 'company_id' => $companyId, 'name' => 'Weekend 10% Off',
            'type' => 'percentage', 'value' => 10, 'start_date' => now()->subDay(), 'end_date' => now()->addDays(6),
            'minimum_amount' => 20, 'maximum_discount' => 15, 'status' => 'active']);
        foreach (['WEEKEND10-A', 'WEEKEND10-B'] as $code) {
            Coupon::create(['id' => $uuid(), 'promotion_id' => $promo->id, 'code' => $code, 'usage_limit' => 50, 'used_count' => rand(0, 5), 'status' => 'active']);
        }

        // Accounting
        $cash = AccountingAccount::create(['id' => $uuid(), 'company_id' => $companyId, 'account_code' => '1001', 'account_name' => 'Cash on Hand', 'account_type' => 'asset', 'status' => 'active']);
        $sales = AccountingAccount::create(['id' => $uuid(), 'company_id' => $companyId, 'account_code' => '4001', 'account_name' => 'Sales Revenue', 'account_type' => 'revenue', 'status' => 'active']);
        $je = JournalEntry::create(['id' => $uuid(), 'company_id' => $companyId, 'reference_type' => 'invoice',
            'reference_id' => $invoices[0]->id, 'entry_date' => now(), 'description' => 'Sale '.$invoices[0]->invoice_number,
            'status' => 'posted', 'created_by' => $user?->id]);
        JournalEntryLine::create(['id' => $uuid(), 'journal_entry_id' => $je->id, 'account_id' => $cash->id, 'debit' => (float) $invoices[0]->total, 'credit' => 0]);
        JournalEntryLine::create(['id' => $uuid(), 'journal_entry_id' => $je->id, 'account_id' => $sales->id, 'debit' => 0, 'credit' => (float) $invoices[0]->total]);

        // Notifications + audit log
        foreach (User::all() as $u) {
            Notification::create(['id' => $uuid(), 'company_id' => $companyId, 'user_id' => $u->id, 'type' => 'LOW_STOCK',
                'title' => 'Low stock alert', 'message' => 'Chocolate Bar 100g is below reorder level.', 'is_read' => false]);
        }
        AuditLog::create(['id' => $uuid(), 'company_id' => $companyId, 'user_id' => $user?->id, 'branch_id' => $branchId,
            'action' => 'SEED', 'module' => 'system', 'table_name' => 'products', 'record_id' => $products[0]->id,
            'old_values' => [], 'new_values' => ['note' => 'Demo data seeded'], 'ip_address' => '127.0.0.1', 'user_agent' => 'seeder']);

        // Open cash session for today (used by POS checkout)

        $this->command->info('Local demo data seeded successfully.');
    }
}
