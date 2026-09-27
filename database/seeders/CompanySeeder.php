<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Company',
                'legal_name' => 'Main Company LLC',
                'phone' => '+1-555-0100',
                'email' => 'info@maincompany.com',
                'address' => '123 Main Street, City, State 12345',
                'tax_number' => 'TAX-123456789',
                'currency' => 'USD',
                'timezone' => 'America/New_York',
                'status' => 'active',
            ]
        );

        $branch = Branch::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'HQ'],
            [
                'name' => 'Headquarters',
                'phone' => '+1-555-0100',
                'address' => '123 Main Street, City, State 12345',
                'status' => 'active',
                'opened_at' => now(),
            ]
        );

        $warehouse = Warehouse::firstOrCreate(
            ['branch_id' => $branch->id, 'code' => 'MAIN'],
            [
                'name' => 'Main Warehouse',
                'address' => '123 Main Street, City, State 12345',
                'status' => 'active',
            ]
        );

        WarehouseLocation::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'code' => 'A-01'],
            [
                'name' => 'Aisle A - Shelf 01',
                'type' => 'shelf',
                'status' => 'active',
            ]
        );

        $ownerEmployee = Employee::firstOrCreate(
            ['employee_code' => 'EMP-001'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'first_name' => 'John',
                'last_name' => 'Owner',
                'phone' => '+1-555-0101',
                'email' => 'owner@maincompany.com',
                'position' => 'Owner',
                'hire_date' => now(),
                'salary' => 0,
                'commission_rate' => 0,
                'status' => 'active',
            ]
        );

        $adminEmployee = Employee::firstOrCreate(
            ['employee_code' => 'EMP-002'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'first_name' => 'Jane',
                'last_name' => 'Admin',
                'phone' => '+1-555-0102',
                'email' => 'admin@maincompany.com',
                'position' => 'Administrator',
                'hire_date' => now(),
                'salary' => 5000,
                'commission_rate' => 0,
                'status' => 'active',
            ]
        );

        $cashierEmployee = Employee::firstOrCreate(
            ['employee_code' => 'EMP-003'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'first_name' => 'Bob',
                'last_name' => 'Cashier',
                'phone' => '+1-555-0103',
                'email' => 'cashier@maincompany.com',
                'position' => 'Cashier',
                'hire_date' => now(),
                'salary' => 3000,
                'commission_rate' => 1.5,
                'status' => 'active',
            ]
        );

        $ownerUser = User::firstOrCreate(
            ['username' => 'owner'],
            [
                'employee_id' => $ownerEmployee->id,
                'email' => 'owner@maincompany.com',
                'password_hash' => Hash::make('password123'),
                'status' => 'active',
            ]
        );

        $adminUser = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'employee_id' => $adminEmployee->id,
                'email' => 'admin@maincompany.com',
                'password_hash' => Hash::make('password123'),
                'status' => 'active',
            ]
        );

        $cashierUser = User::firstOrCreate(
            ['username' => 'cashier'],
            [
                'employee_id' => $cashierEmployee->id,
                'email' => 'cashier@maincompany.com',
                'password_hash' => Hash::make('password123'),
                'status' => 'active',
            ]
        );

        $ownerRole = Role::where('name', 'Owner')->first();
        $adminRole = Role::where('name', 'Administrator')->first();
        $cashierRole = Role::where('name', 'Cashier')->first();

        if ($ownerRole) {
            $ownerUser->roles()->syncWithoutDetaching([$ownerRole->id]);
        }
        if ($adminRole) {
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
        }
        if ($cashierRole) {
            $cashierUser->roles()->syncWithoutDetaching([$cashierRole->id]);
        }

        $branch->update(['manager_id' => $ownerEmployee->id]);
        $warehouse->update(['manager_id' => $ownerEmployee->id]);

        $cashRegister = CashRegister::firstOrCreate(
            ['branch_id' => $branch->id, 'terminal_number' => 'POS-001'],
            [
                'name' => 'Main POS Register',
                'status' => 'active',
            ]
        );

        PaymentMethod::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Cash'],
            [
                'type' => 'cash',
                'provider' => null,
                'fee_rate' => 0,
                'status' => 'active',
            ]
        );

        PaymentMethod::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Credit Card'],
            [
                'type' => 'card',
                'provider' => 'stripe',
                'fee_rate' => 2.9,
                'status' => 'active',
            ]
        );

        PaymentMethod::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Bank Transfer'],
            [
                'type' => 'bank_transfer',
                'provider' => null,
                'fee_rate' => 0.5,
                'status' => 'active',
            ]
        );

        AccountingAccount::firstOrCreate(
            ['company_id' => $company->id, 'account_code' => '1000'],
            [
                'account_name' => 'Cash',
                'account_type' => 'asset',
                'status' => 'active',
            ]
        );

        AccountingAccount::firstOrCreate(
            ['company_id' => $company->id, 'account_code' => '1100'],
            [
                'account_name' => 'Accounts Receivable',
                'account_type' => 'asset',
                'status' => 'active',
            ]
        );

        AccountingAccount::firstOrCreate(
            ['company_id' => $company->id, 'account_code' => '1200'],
            [
                'account_name' => 'Inventory',
                'account_type' => 'asset',
                'status' => 'active',
            ]
        );

        AccountingAccount::firstOrCreate(
            ['company_id' => $company->id, 'account_code' => '2000'],
            [
                'account_name' => 'Accounts Payable',
                'account_type' => 'liability',
                'status' => 'active',
            ]
        );

        AccountingAccount::firstOrCreate(
            ['company_id' => $company->id, 'account_code' => '3000'],
            [
                'account_name' => 'Owner\'s Equity',
                'account_type' => 'equity',
                'status' => 'active',
            ]
        );

        AccountingAccount::firstOrCreate(
            ['company_id' => $company->id, 'account_code' => '4000'],
            [
                'account_name' => 'Sales Revenue',
                'account_type' => 'revenue',
                'status' => 'active',
            ]
        );

        AccountingAccount::firstOrCreate(
            ['company_id' => $company->id, 'account_code' => '5000'],
            [
                'account_name' => 'Cost of Goods Sold',
                'account_type' => 'expense',
                'status' => 'active',
            ]
        );

        AccountingAccount::firstOrCreate(
            ['company_id' => $company->id, 'account_code' => '6000'],
            [
                'account_name' => 'Operating Expenses',
                'account_type' => 'expense',
                'status' => 'active',
            ]
        );
    }
}
