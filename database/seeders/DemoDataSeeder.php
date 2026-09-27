<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = '01a0e0f9-5b50-70bc-9a46-af4696a5358f';
        $branchId = '01a0e0f9-60ff-70e0-ad62-013c22580c88';
        $warehouseId = '01a0e0f9-66ad-71f4-8241-dce1e35fa6a3';
        $cashMethodId = '01a0e0f9-ad17-70e8-bbc7-4526b2d426fa';

        if (Invoice::where('invoice_number', 'like', 'INV-%')->count() >= 3 && Payment::whereHas('invoice', fn ($q) => $q->where('invoice_number', 'like', 'INV-%'))->count() >= 3) {
            $this->command->info('Demo data already seeded, skipping.');

            return;
        }

        $units = [];
        foreach ([['Piece', 'pc'], ['Kilogram', 'kg'], ['Liter', 'L']] as [$name, $symbol]) {
            $units[$symbol] = Unit::firstOrCreate(
                ['company_id' => $companyId, 'symbol' => $symbol],
                ['id' => (string) Str::uuid(), 'name' => $name]
            );
        }

        $brands = [];
        foreach ([['Acme', 'ACME'], ['Globex', 'GLBX']] as [$name, $code]) {
            $brands[$name] = Brand::firstOrCreate(
                ['company_id' => $companyId, 'code' => $code],
                ['id' => (string) Str::uuid(), 'name' => $name, 'status' => 'active']
            );
        }

        $categories = [];
        foreach ([['Beverages', 'BEV'], ['Snacks', 'SNK'], ['Household', 'HSH']] as [$name, $code]) {
            $categories[$name] = Category::firstOrCreate(
                ['company_id' => $companyId, 'code' => $code],
                ['id' => (string) Str::uuid(), 'name' => $name, 'status' => 'active']
            );
        }

        $items = [
            ['Cola 330ml', 'BEV-COLA330', 'Beverages', 'Acme', 'pc', 0.60, 1.50, 120],
            ['Orange Juice 1L', 'BEV-OJ1L', 'Beverages', 'Acme', 'L', 1.10, 2.80, 80],
            ['Mineral Water 500ml', 'BEV-WTR500', 'Beverages', 'Globex', 'pc', 0.25, 0.80, 200],
            ['Potato Chips 150g', 'SNK-CHIP150', 'Snacks', 'Globex', 'pc', 0.90, 2.20, 150],
            ['Chocolate Bar 100g', 'SNK-CHOC100', 'Snacks', 'Acme', 'pc', 0.70, 1.90, 4],
            ['Rice 5kg', 'SNK-RICE5K', 'Snacks', 'Globex', 'kg', 3.20, 5.50, 60],
            ['Dish Soap 750ml', 'HSH-DISH750', 'Household', 'Acme', 'pc', 1.00, 2.60, 90],
            ['Laundry Powder 2kg', 'HSH-LAUN2K', 'Household', 'Globex', 'kg', 2.40, 4.90, 45],
            ['Paper Towels 4pk', 'HSH-PT4PK', 'Household', 'Acme', 'pc', 1.80, 3.90, 0],
            ['Green Tea 25pk', 'BEV-GTEA25', 'Beverages', 'Globex', 'pc', 1.20, 2.90, 70],
            ['Peanuts 200g', 'SNK-PEA200', 'Snacks', 'Acme', 'pc', 0.85, 2.10, 110],
            ['Trash Bags 30pk', 'HSH-TRB30', 'Household', 'Globex', 'pc', 1.50, 3.40, 65],
        ];

        $products = [];
        foreach ($items as [$name, $sku, $cat, $brand, $unit, $cost, $price, $qty]) {
            $product = Product::firstOrCreate(
                ['sku' => $sku],
                [
                    'id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'category_id' => $categories[$cat]->id,
                    'brand_id' => $brands[$brand]->id,
                    'unit_id' => $units[$unit]->id,
                    'barcode' => 'BC'.$sku,
                    'name' => $name,
                    'cost_price' => $cost,
                    'selling_price' => $price,
                    'reorder_level' => 10,
                    'status' => 'active',
                ]
            );
            if (DB::table('stock')->where('product_id', $product->id)->exists()) {
                $products[] = $product;

                continue;
            }
            DB::table('stock')->insert([
                'id' => (string) Str::uuid(),
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'quantity' => $qty,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
                'average_cost' => $cost,
            ]);
            $products[] = $product;
        }

        $groups = [];
        foreach ([['Retail', '0'], ['Wholesale', '5']] as [$name, $discount]) {
            $groups[$name] = CustomerGroup::firstOrCreate(
                ['company_id' => $companyId, 'name' => $name],
                ['id' => (string) Str::uuid(), 'discount_rate' => $discount, 'status' => 'active']
            );
        }

        $customers = [];
        foreach ([
            ['CUST-001', 'John Carter', '555-0101', 'Retail'],
            ['CUST-002', 'Mary Smith', '555-0102', 'Retail'],
            ['CUST-003', 'City Mart', '555-0103', 'Wholesale'],
            ['CUST-004', 'Ali Hassan', '555-0104', 'Retail'],
            ['CUST-005', 'Green Grocers', '555-0105', 'Wholesale'],
        ] as [$code, $name, $phone, $group]) {
            $customers[] = Customer::firstOrCreate(
                ['customer_code' => $code],
                [
                    'id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'name' => $name,
                    'phone' => $phone,
                    'customer_group_id' => $groups[$group]->id,
                    'credit_limit' => 500,
                    'credit_days' => 30,
                    'loyalty_points' => rand(0, 120),
                    'status' => 'active',
                ]
            );
        }

        foreach ([
            ['SUP-001', 'Fresh Foods Co.', '555-0201'],
            ['SUP-002', 'Home Essentials Ltd.', '555-0202'],
            ['SUP-003', 'Beverage World', '555-0203'],
        ] as [$code, $name, $phone]) {
            Supplier::firstOrCreate(
                ['supplier_code' => $code],
                [
                    'id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'name' => $name,
                    'phone' => $phone,
                    'status' => 'active',
                ]
            );
        }

        foreach ([0, 1, 2] as $dayAgo) {
            $date = now()->subDays($dayAgo);
            $pick = array_slice($products, $dayAgo * 2, 3);
            $subtotal = 0;
            foreach ($pick as $p) {
                $subtotal += 2 * (float) $p->selling_price;
            }
            $invoice = Invoice::firstOrCreate(
                ['invoice_number' => 'INV-'.$date->format('Ymd').'-100'.$dayAgo],
                [
                    'id' => (string) Str::uuid(),
                    'branch_id' => $branchId,
                    'customer_id' => $customers[$dayAgo % count($customers)]->id,
                    'invoice_date' => $date,
                    'subtotal' => $subtotal,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => $subtotal,
                    'paid_amount' => $subtotal,
                    'due_amount' => 0,
                    'status' => 'paid',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
            if ($invoice->items()->count() === 0) {
                foreach ($pick as $p) {
                    InvoiceItem::create([
                        'id' => (string) Str::uuid(),
                        'invoice_id' => $invoice->id,
                        'product_id' => $p->id,
                        'quantity' => 2,
                        'unit_price' => $p->selling_price,
                        'discount' => 0,
                        'tax' => 0,
                        'cost_price' => $p->cost_price,
                        'total' => 2 * (float) $p->selling_price,
                    ]);
                }
            }
            Payment::firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'customer_id' => $invoice->customer_id,
                    'payment_method_id' => $cashMethodId,
                    'amount' => $subtotal,
                    'status' => 'completed',
                    'payment_date' => $date,
                    'received_by' => User::first()?->id,
                ]
            );
        }

        $this->command->info('Demo data seeded successfully.');
    }
}
