<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['code' => 'dashboard.view', 'name' => 'View Dashboard', 'module' => 'dashboard'],

            // Companies
            ['code' => 'companies.view', 'name' => 'View Companies', 'module' => 'companies'],
            ['code' => 'companies.create', 'name' => 'Create Companies', 'module' => 'companies'],
            ['code' => 'companies.update', 'name' => 'Update Companies', 'module' => 'companies'],
            ['code' => 'companies.delete', 'name' => 'Delete Companies', 'module' => 'companies'],

            // Branches
            ['code' => 'branches.view', 'name' => 'View Branches', 'module' => 'branches'],
            ['code' => 'branches.create', 'name' => 'Create Branches', 'module' => 'branches'],
            ['code' => 'branches.update', 'name' => 'Update Branches', 'module' => 'branches'],
            ['code' => 'branches.delete', 'name' => 'Delete Branches', 'module' => 'branches'],

            // Warehouses
            ['code' => 'warehouses.view', 'name' => 'View Warehouses', 'module' => 'warehouses'],
            ['code' => 'warehouses.create', 'name' => 'Create Warehouses', 'module' => 'warehouses'],
            ['code' => 'warehouses.update', 'name' => 'Update Warehouses', 'module' => 'warehouses'],
            ['code' => 'warehouses.delete', 'name' => 'Delete Warehouses', 'module' => 'warehouses'],

            // Products
            ['code' => 'products.view', 'name' => 'View Products', 'module' => 'products'],
            ['code' => 'products.create', 'name' => 'Create Products', 'module' => 'products'],
            ['code' => 'products.update', 'name' => 'Update Products', 'module' => 'products'],
            ['code' => 'products.delete', 'name' => 'Delete Products', 'module' => 'products'],
            ['code' => 'products.price_override', 'name' => 'Override Product Price', 'module' => 'products'],

            // Categories
            ['code' => 'categories.view', 'name' => 'View Categories', 'module' => 'categories'],
            ['code' => 'categories.create', 'name' => 'Create Categories', 'module' => 'categories'],
            ['code' => 'categories.update', 'name' => 'Update Categories', 'module' => 'categories'],
            ['code' => 'categories.delete', 'name' => 'Delete Categories', 'module' => 'categories'],

            // Brands
            ['code' => 'brands.view', 'name' => 'View Brands', 'module' => 'brands'],
            ['code' => 'brands.create', 'name' => 'Create Brands', 'module' => 'brands'],
            ['code' => 'brands.update', 'name' => 'Update Brands', 'module' => 'brands'],
            ['code' => 'brands.delete', 'name' => 'Delete Brands', 'module' => 'brands'],

            // Units
            ['code' => 'units.view', 'name' => 'View Units', 'module' => 'units'],
            ['code' => 'units.create', 'name' => 'Create Units', 'module' => 'units'],
            ['code' => 'units.update', 'name' => 'Update Units', 'module' => 'units'],
            ['code' => 'units.delete', 'name' => 'Delete Units', 'module' => 'units'],

            // Inventory - Stock
            ['code' => 'stock.view', 'name' => 'View Stock', 'module' => 'inventory'],
            ['code' => 'stock.adjust', 'name' => 'Adjust Stock', 'module' => 'inventory'],
            ['code' => 'stock.transfer', 'name' => 'Transfer Stock', 'module' => 'inventory'],
            ['code' => 'stock.count', 'name' => 'Count Stock', 'module' => 'inventory'],

            // Inventory - Movements
            ['code' => 'stock_movements.view', 'name' => 'View Stock Movements', 'module' => 'inventory'],

            // Sales - POS
            ['code' => 'pos.access', 'name' => 'Access POS', 'module' => 'sales'],
            ['code' => 'pos.hold_order', 'name' => 'Hold Order', 'module' => 'sales'],
            ['code' => 'pos.resume_order', 'name' => 'Resume Order', 'module' => 'sales'],
            ['code' => 'pos.discount', 'name' => 'Apply Discount', 'module' => 'sales'],
            ['code' => 'pos.price_override', 'name' => 'Override Price', 'module' => 'sales'],
            ['code' => 'pos.refund', 'name' => 'Process Refund', 'module' => 'sales'],

            // Sales - Orders
            ['code' => 'sales_orders.view', 'name' => 'View Sales Orders', 'module' => 'sales'],
            ['code' => 'sales_orders.create', 'name' => 'Create Sales Orders', 'module' => 'sales'],
            ['code' => 'sales_orders.update', 'name' => 'Update Sales Orders', 'module' => 'sales'],
            ['code' => 'sales_orders.cancel', 'name' => 'Cancel Sales Orders', 'module' => 'sales'],

            // Sales - Quotations
            ['code' => 'quotations.view', 'name' => 'View Quotations', 'module' => 'sales'],
            ['code' => 'quotations.create', 'name' => 'Create Quotations', 'module' => 'sales'],
            ['code' => 'quotations.update', 'name' => 'Update Quotations', 'module' => 'sales'],
            ['code' => 'quotations.convert', 'name' => 'Convert Quotation', 'module' => 'sales'],

            // Sales - Invoices
            ['code' => 'invoices.view', 'name' => 'View Invoices', 'module' => 'sales'],
            ['code' => 'invoices.create', 'name' => 'Create Invoices', 'module' => 'sales'],
            ['code' => 'invoices.update', 'name' => 'Update Invoices', 'module' => 'sales'],
            ['code' => 'invoices.cancel', 'name' => 'Cancel Invoices', 'module' => 'sales'],
            ['code' => 'invoices.print', 'name' => 'Print Invoices', 'module' => 'sales'],

            // Sales - Returns
            ['code' => 'sales_returns.view', 'name' => 'View Sales Returns', 'module' => 'sales'],
            ['code' => 'sales_returns.create', 'name' => 'Create Sales Returns', 'module' => 'sales'],
            ['code' => 'sales_returns.approve', 'name' => 'Approve Sales Returns', 'module' => 'sales'],

            // Refunds
            ['code' => 'refunds.view', 'name' => 'View Refunds', 'module' => 'sales'],
            ['code' => 'refunds.create', 'name' => 'Create Refunds', 'module' => 'sales'],
            ['code' => 'refunds.approve', 'name' => 'Approve Refunds', 'module' => 'sales'],

            // Purchasing - Purchase Orders
            ['code' => 'purchase_orders.view', 'name' => 'View Purchase Orders', 'module' => 'purchasing'],
            ['code' => 'purchase_orders.create', 'name' => 'Create Purchase Orders', 'module' => 'purchasing'],
            ['code' => 'purchase_orders.update', 'name' => 'Update Purchase Orders', 'module' => 'purchasing'],
            ['code' => 'purchase_orders.approve', 'name' => 'Approve Purchase Orders', 'module' => 'purchasing'],
            ['code' => 'purchase_orders.cancel', 'name' => 'Cancel Purchase Orders', 'module' => 'purchasing'],

            // Purchasing - Receipts
            ['code' => 'purchase_receipts.view', 'name' => 'View Purchase Receipts', 'module' => 'purchasing'],
            ['code' => 'purchase_receipts.create', 'name' => 'Create Purchase Receipts', 'module' => 'purchasing'],

            // Purchasing - Returns
            ['code' => 'purchase_returns.view', 'name' => 'View Purchase Returns', 'module' => 'purchasing'],
            ['code' => 'purchase_returns.create', 'name' => 'Create Purchase Returns', 'module' => 'purchasing'],
            ['code' => 'purchase_returns.approve', 'name' => 'Approve Purchase Returns', 'module' => 'purchasing'],

            // Suppliers
            ['code' => 'suppliers.view', 'name' => 'View Suppliers', 'module' => 'suppliers'],
            ['code' => 'suppliers.create', 'name' => 'Create Suppliers', 'module' => 'suppliers'],
            ['code' => 'suppliers.update', 'name' => 'Update Suppliers', 'module' => 'suppliers'],
            ['code' => 'suppliers.delete', 'name' => 'Delete Suppliers', 'module' => 'suppliers'],

            // Customers
            ['code' => 'customers.view', 'name' => 'View Customers', 'module' => 'customers'],
            ['code' => 'customers.create', 'name' => 'Create Customers', 'module' => 'customers'],
            ['code' => 'customers.update', 'name' => 'Update Customers', 'module' => 'customers'],
            ['code' => 'customers.delete', 'name' => 'Delete Customers', 'module' => 'customers'],
            ['code' => 'customers.credit_limit', 'name' => 'Manage Credit Limits', 'module' => 'customers'],

            // Customer Groups
            ['code' => 'customer_groups.view', 'name' => 'View Customer Groups', 'module' => 'customers'],
            ['code' => 'customer_groups.create', 'name' => 'Create Customer Groups', 'module' => 'customers'],
            ['code' => 'customer_groups.update', 'name' => 'Update Customer Groups', 'module' => 'customers'],
            ['code' => 'customer_groups.delete', 'name' => 'Delete Customer Groups', 'module' => 'customers'],

            // Payments
            ['code' => 'payments.view', 'name' => 'View Payments', 'module' => 'payments'],
            ['code' => 'payments.create', 'name' => 'Create Payments', 'module' => 'payments'],
            ['code' => 'payments.refund', 'name' => 'Refund Payments', 'module' => 'payments'],

            // Cash Registers
            ['code' => 'cash_registers.view', 'name' => 'View Cash Registers', 'module' => 'cash'],
            ['code' => 'cash_registers.manage', 'name' => 'Manage Cash Registers', 'module' => 'cash'],

            // Cash Sessions
            ['code' => 'cash_sessions.view', 'name' => 'View Cash Sessions', 'module' => 'cash'],
            ['code' => 'cash_sessions.open', 'name' => 'Open Cash Session', 'module' => 'cash'],
            ['code' => 'cash_sessions.close', 'name' => 'Close Cash Session', 'module' => 'cash'],
            ['code' => 'cash_sessions.reconcile', 'name' => 'Reconcile Cash Session', 'module' => 'cash'],

            // Bank Accounts
            ['code' => 'bank_accounts.view', 'name' => 'View Bank Accounts', 'module' => 'banking'],
            ['code' => 'bank_accounts.create', 'name' => 'Create Bank Accounts', 'module' => 'banking'],
            ['code' => 'bank_accounts.update', 'name' => 'Update Bank Accounts', 'module' => 'banking'],
            ['code' => 'bank_accounts.delete', 'name' => 'Delete Bank Accounts', 'module' => 'banking'],

            // Bank Transactions
            ['code' => 'bank_transactions.view', 'name' => 'View Bank Transactions', 'module' => 'banking'],
            ['code' => 'bank_transactions.create', 'name' => 'Create Bank Transactions', 'module' => 'banking'],
            ['code' => 'bank_transactions.reconcile', 'name' => 'Reconcile Bank', 'module' => 'banking'],

            // Expenses
            ['code' => 'expenses.view', 'name' => 'View Expenses', 'module' => 'expenses'],
            ['code' => 'expenses.create', 'name' => 'Create Expenses', 'module' => 'expenses'],
            ['code' => 'expenses.update', 'name' => 'Update Expenses', 'module' => 'expenses'],
            ['code' => 'expenses.approve', 'name' => 'Approve Expenses', 'module' => 'expenses'],
            ['code' => 'expenses.delete', 'name' => 'Delete Expenses', 'module' => 'expenses'],

            // Expense Categories
            ['code' => 'expense_categories.view', 'name' => 'View Expense Categories', 'module' => 'expenses'],
            ['code' => 'expense_categories.create', 'name' => 'Create Expense Categories', 'module' => 'expenses'],
            ['code' => 'expense_categories.update', 'name' => 'Update Expense Categories', 'module' => 'expenses'],
            ['code' => 'expense_categories.delete', 'name' => 'Delete Expense Categories', 'module' => 'expenses'],

            // Employees
            ['code' => 'employees.view', 'name' => 'View Employees', 'module' => 'employees'],
            ['code' => 'employees.create', 'name' => 'Create Employees', 'module' => 'employees'],
            ['code' => 'employees.update', 'name' => 'Update Employees', 'module' => 'employees'],
            ['code' => 'employees.delete', 'name' => 'Delete Employees', 'module' => 'employees'],

            // Attendance
            ['code' => 'attendance.view', 'name' => 'View Attendance', 'module' => 'employees'],
            ['code' => 'attendance.manage', 'name' => 'Manage Attendance', 'module' => 'employees'],

            // Shifts
            ['code' => 'shifts.view', 'name' => 'View Shifts', 'module' => 'employees'],
            ['code' => 'shifts.create', 'name' => 'Create Shifts', 'module' => 'employees'],
            ['code' => 'shifts.update', 'name' => 'Update Shifts', 'module' => 'employees'],
            ['code' => 'shifts.delete', 'name' => 'Delete Shifts', 'module' => 'employees'],

            // Commissions
            ['code' => 'commissions.view', 'name' => 'View Commissions', 'module' => 'employees'],
            ['code' => 'commissions.manage', 'name' => 'Manage Commissions', 'module' => 'employees'],

            // Promotions
            ['code' => 'promotions.view', 'name' => 'View Promotions', 'module' => 'promotions'],
            ['code' => 'promotions.create', 'name' => 'Create Promotions', 'module' => 'promotions'],
            ['code' => 'promotions.update', 'name' => 'Update Promotions', 'module' => 'promotions'],
            ['code' => 'promotions.delete', 'name' => 'Delete Promotions', 'module' => 'promotions'],

            // Coupons
            ['code' => 'coupons.view', 'name' => 'View Coupons', 'module' => 'promotions'],
            ['code' => 'coupons.create', 'name' => 'Create Coupons', 'module' => 'promotions'],
            ['code' => 'coupons.update', 'name' => 'Update Coupons', 'module' => 'promotions'],
            ['code' => 'coupons.delete', 'name' => 'Delete Coupons', 'module' => 'promotions'],

            // Accounting
            ['code' => 'accounting.accounts.view', 'name' => 'View Chart of Accounts', 'module' => 'accounting'],
            ['code' => 'accounting.accounts.create', 'name' => 'Create Accounts', 'module' => 'accounting'],
            ['code' => 'accounting.accounts.update', 'name' => 'Update Accounts', 'module' => 'accounting'],
            ['code' => 'accounting.accounts.delete', 'name' => 'Delete Accounts', 'module' => 'accounting'],
            ['code' => 'accounting.journal_entries.view', 'name' => 'View Journal Entries', 'module' => 'accounting'],
            ['code' => 'accounting.journal_entries.create', 'name' => 'Create Journal Entries', 'module' => 'accounting'],
            ['code' => 'accounting.journal_entries.post', 'name' => 'Post Journal Entries', 'module' => 'accounting'],
            ['code' => 'accounting.reports.view', 'name' => 'View Accounting Reports', 'module' => 'accounting'],

            // Reports
            ['code' => 'reports.sales', 'name' => 'Sales Reports', 'module' => 'reports'],
            ['code' => 'reports.profit', 'name' => 'Profit Reports', 'module' => 'reports'],
            ['code' => 'reports.inventory', 'name' => 'Inventory Reports', 'module' => 'reports'],
            ['code' => 'reports.purchases', 'name' => 'Purchase Reports', 'module' => 'reports'],
            ['code' => 'reports.expenses', 'name' => 'Expense Reports', 'module' => 'reports'],
            ['code' => 'reports.customers', 'name' => 'Customer Reports', 'module' => 'reports'],
            ['code' => 'reports.suppliers', 'name' => 'Supplier Reports', 'module' => 'reports'],
            ['code' => 'reports.employees', 'name' => 'Employee Reports', 'module' => 'reports'],
            ['code' => 'reports.branches', 'name' => 'Branch Reports', 'module' => 'reports'],
            ['code' => 'reports.cash', 'name' => 'Cash Reports', 'module' => 'reports'],
            ['code' => 'reports.tax', 'name' => 'Tax Reports', 'module' => 'reports'],

            // Security - Users
            ['code' => 'users.view', 'name' => 'View Users', 'module' => 'security'],
            ['code' => 'users.create', 'name' => 'Create Users', 'module' => 'security'],
            ['code' => 'users.update', 'name' => 'Update Users', 'module' => 'security'],
            ['code' => 'users.delete', 'name' => 'Delete Users', 'module' => 'security'],
            ['code' => 'users.reset_password', 'name' => 'Reset Password', 'module' => 'security'],

            // Security - Roles
            ['code' => 'roles.view', 'name' => 'View Roles', 'module' => 'security'],
            ['code' => 'roles.create', 'name' => 'Create Roles', 'module' => 'security'],
            ['code' => 'roles.update', 'name' => 'Update Roles', 'module' => 'security'],
            ['code' => 'roles.delete', 'name' => 'Delete Roles', 'module' => 'security'],

            // Security - Permissions
            ['code' => 'permissions.view', 'name' => 'View Permissions', 'module' => 'security'],
            ['code' => 'permissions.assign', 'name' => 'Assign Permissions', 'module' => 'security'],

            // Audit Logs
            ['code' => 'audit_logs.view', 'name' => 'View Audit Logs', 'module' => 'security'],

            // Approvals
            ['code' => 'approvals.view', 'name' => 'View Approvals', 'module' => 'security'],
            ['code' => 'approvals.approve', 'name' => 'Approve Requests', 'module' => 'security'],
            ['code' => 'approvals.reject', 'name' => 'Reject Requests', 'module' => 'security'],

            // Settings
            ['code' => 'settings.view', 'name' => 'View Settings', 'module' => 'settings'],
            ['code' => 'settings.update', 'name' => 'Update Settings', 'module' => 'settings'],

            // Notifications
            ['code' => 'notifications.view', 'name' => 'View Notifications', 'module' => 'notifications'],
            ['code' => 'notifications.manage', 'name' => 'Manage Notifications', 'module' => 'notifications'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['code' => $permission['code']], $permission);
        }
    }
}
