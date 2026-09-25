<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\CustomerGroup;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductTax;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserBranch;
use App\Models\UserRole;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PosDemoSeeder extends Seeder
{
    public function run(): void
    {
        // --- Roles & permissions ---
        $admin = Role::firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'manager'], ['guard_name' => 'web']);
        $cashier = Role::firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);

        $permNames = ['products.view', 'products.manage', 'sales.create', 'stock.view', 'purchases.manage', 'users.manage'];
        $perms = [];
        foreach ($permNames as $name) {
            $perms[$name] = Permission::firstOrCreate(['name' => $name], ['guard_name' => 'web']);
        }

        foreach ($perms as $perm) {
            RolePermission::firstOrCreate(['role_id' => $admin->id, 'permission_id' => $perm->id]);
        }
        foreach (['products.view', 'products.manage', 'sales.create', 'stock.view', 'purchases.manage'] as $name) {
            RolePermission::firstOrCreate(['role_id' => $manager->id, 'permission_id' => $perms[$name]->id]);
        }
        foreach (['products.view', 'sales.create', 'stock.view'] as $name) {
            RolePermission::firstOrCreate(['role_id' => $cashier->id, 'permission_id' => $perms[$name]->id]);
        }

        // --- Org: branch + warehouse ---
        $branch = Branch::firstOrCreate(['code' => 'MAIN'], ['name' => 'Main Branch', 'is_active' => true]);
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'MAIN-WH'],
            ['name' => 'Main Warehouse', 'branch_id' => $branch->id, 'is_active' => true]
        );

        // --- Users + employees + links ---
        $adminUser = User::updateOrCreate(
            ['username' => 'admin'],
            ['name' => 'Admin POS', 'email' => 'admin@pos.local', 'password' => Hash::make('password'), 'status' => 'active']
        );
        $cashierUser = User::updateOrCreate(
            ['username' => 'cashier1'],
            ['name' => 'Cashier One', 'email' => 'cashier1@pos.local', 'password' => Hash::make('password'), 'status' => 'active']
        );

        Employee::firstOrCreate(
            ['user_id' => $adminUser->id],
            ['branch_id' => $branch->id, 'first_name' => 'Admin', 'last_name' => 'POS', 'position' => 'Administrator', 'status' => 'active']
        );
        Employee::firstOrCreate(
            ['user_id' => $cashierUser->id],
            ['branch_id' => $branch->id, 'first_name' => 'Cashier', 'last_name' => 'One', 'position' => 'Cashier', 'status' => 'active']
        );

        UserRole::firstOrCreate(['user_id' => $adminUser->id, 'role_id' => $admin->id]);
        UserRole::firstOrCreate(['user_id' => $cashierUser->id, 'role_id' => $cashier->id]);

        UserBranch::firstOrCreate(['user_id' => $adminUser->id, 'branch_id' => $branch->id]);
        UserBranch::firstOrCreate(['user_id' => $cashierUser->id, 'branch_id' => $branch->id]);

        // --- Payment methods ---
        $cash = PaymentMethod::firstOrCreate(['code' => 'CASH'], ['name' => 'Cash', 'is_active' => true]);
        PaymentMethod::firstOrCreate(['code' => 'KHQR'], ['name' => 'ABA KHQR', 'is_active' => true]);
        PaymentMethod::firstOrCreate(['code' => 'CARD'], ['name' => 'Visa', 'is_active' => true]);

        // --- Catalog ---
        $beverages = Category::firstOrCreate(['slug' => 'beverages'], ['name' => 'Beverages', 'is_active' => true]);
        $snacks = Category::firstOrCreate(['slug' => 'snacks'], ['name' => 'Snacks', 'is_active' => true]);
        $coca = Brand::firstOrCreate(['slug' => 'coca-cola'], ['name' => 'Coca-Cola', 'is_active' => true]);
        $abc = Brand::firstOrCreate(['slug' => 'abc'], ['name' => 'ABC', 'is_active' => true]);
        $piece = Unit::firstOrCreate(['name' => 'Piece'], ['short_name' => 'pcs', 'is_active' => true]);
        $box = Unit::firstOrCreate(['name' => 'Box'], ['short_name' => 'box', 'is_active' => true]);
        $vat = Tax::firstOrCreate(['name' => 'VAT 10%'], ['rate' => 10, 'is_active' => true]);

        $cola = Product::updateOrCreate(
            ['sku' => 'COKE-330'],
            ['name' => 'Coca-Cola 330ml', 'category_id' => $beverages->id, 'brand_id' => $coca->id,
                'unit_id' => $piece->id, 'purchase_price' => 1500, 'sale_price' => 2500,
                'tax_rate' => 10, 'type' => 'standard', 'is_active' => true]
        );
        $soy = Product::updateOrCreate(
            ['sku' => 'ABC-SOY'],
            ['name' => 'ABC Soy Milk', 'category_id' => $beverages->id, 'brand_id' => $abc->id,
                'unit_id' => $piece->id, 'purchase_price' => 2000, 'sale_price' => 3000,
                'tax_rate' => 10, 'type' => 'standard', 'is_active' => true]
        );

        $colaUnit = ProductUnit::updateOrCreate(
            ['product_id' => $cola->id, 'unit_id' => $piece->id],
            ['conversion_qty' => 1, 'sale_price' => 2500, 'barcode' => '885000000001', 'is_base' => true]
        );
        $soyUnit = ProductUnit::updateOrCreate(
            ['product_id' => $soy->id, 'unit_id' => $piece->id],
            ['conversion_qty' => 1, 'sale_price' => 3000, 'barcode' => '885000000002', 'is_base' => true]
        );

        ProductTax::firstOrCreate(['product_id' => $cola->id, 'tax_id' => $vat->id]);
        ProductTax::firstOrCreate(['product_id' => $soy->id, 'tax_id' => $vat->id]);

        Stock::updateOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_unit_id' => $colaUnit->id],
            ['quantity' => 500]
        );
        Stock::updateOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_unit_id' => $soyUnit->id],
            ['quantity' => 300]
        );

        // --- Suppliers ---
        $supplier = Supplier::firstOrCreate(
            ['code' => 'SUP-001'],
            ['name' => 'Demo Supplier', 'email' => 'supplier@demo.local', 'status' => 'active']
        );
        SupplierProduct::firstOrCreate(
            ['supplier_id' => $supplier->id, 'product_id' => $cola->id],
            ['cost_price' => 1500]
        );
        SupplierProduct::firstOrCreate(
            ['supplier_id' => $supplier->id, 'product_id' => $soy->id],
            ['cost_price' => 2000]
        );

        // --- Customers ---
        $retail = CustomerGroup::firstOrCreate(['name' => 'Retail'], ['discount_percent' => 0]);
        $wholesale = CustomerGroup::firstOrCreate(['name' => 'Wholesale'], ['discount_percent' => 5]);

        $walkin = Customer::firstOrCreate(
            ['code' => 'CUST-WALKIN'],
            ['name' => 'Walk-in', 'customer_group_id' => $retail->id, 'status' => 'active']
        );
        $ws = Customer::firstOrCreate(
            ['code' => 'CUST-001'],
            ['name' => 'Wholesale Customer 1', 'customer_group_id' => $wholesale->id, 'status' => 'active']
        );
        CustomerAccount::firstOrCreate(['customer_id' => $walkin->id], ['balance' => 0, 'credit_limit' => 0]);
        CustomerAccount::firstOrCreate(['customer_id' => $ws->id], ['balance' => 0, 'credit_limit' => 1000000]);

        // --- Cash register + open session ---
        $register = CashRegister::firstOrCreate(
            ['code' => 'REG-01'],
            ['branch_id' => $branch->id, 'name' => 'Counter 1', 'is_active' => true]
        );
        CashSession::firstOrCreate(
            ['cash_register_id' => $register->id, 'user_id' => $cashierUser->id, 'status' => 'open'],
            ['opening_balance' => 100000, 'opened_at' => now()]
        );

        // --- Discount + settings ---
        Discount::firstOrCreate(
            ['name' => 'Opening Promo'],
            ['type' => 'percentage', 'value' => 5, 'is_active' => true]
        );
        Setting::updateOrCreate(['branch_id' => null, 'key' => 'shop_name'], ['value' => 'Demo POS']);
        Setting::updateOrCreate(['branch_id' => $branch->id, 'key' => 'receipt_footer'], ['value' => 'Thank you!']);
    }
}
