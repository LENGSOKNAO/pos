<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Models\CustomerGroup;
use App\Models\CustomerPayment;
use App\Models\DeliveryAddress;
use App\Models\DeliveryDriver;
use App\Models\DeliveryOrder;
use App\Models\DeliveryZone;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductTax;
use App\Models\ProductUnit;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesReturnRefund;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\SupplierAddress;
use App\Models\SupplierContact;
use App\Models\SupplierPayment;
use App\Models\SupplierProduct;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PosBulkSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::count() > 10) {
            return;
        }

        $branch = Branch::firstOrCreate(['code' => 'MAIN'], ['name' => 'Main Branch', 'is_active' => true]);
        $warehouse = Warehouse::firstOrCreate(['code' => 'MAIN-WH'], ['name' => 'Main Warehouse', 'branch_id' => $branch->id, 'is_active' => true]);
        $cashier = User::where('username', 'cashier1')->first() ?? User::first();
        $cash = PaymentMethod::firstOrCreate(['code' => 'CASH'], ['name' => 'Cash', 'is_active' => true]);
        $khqr = PaymentMethod::firstOrCreate(['code' => 'KHQR'], ['name' => 'ABA KHQR', 'is_active' => true]);
        PaymentMethod::firstOrCreate(['code' => 'CARD'], ['name' => 'Visa', 'is_active' => true]);
        PaymentMethod::firstOrCreate(['code' => 'BANK'], ['name' => 'Bank Transfer', 'is_active' => true]);

        // --- Catalog: categories / brands / units ---
        $catNames = ['Beverages', 'Snacks', 'Noodles & Rice', 'Dairy', 'Household', 'Baby Care', 'Frozen', 'Cigarettes'];
        $cats = [];
        foreach ($catNames as $n) {
            $cats[$n] = Category::firstOrCreate(['slug' => Str::slug($n)], ['name' => $n, 'is_active' => true]);
        }
        foreach (['Coca-Cola', 'Pepsi', 'ABC', 'Anchor', 'Nestle', 'Oishi', 'Mama', 'Tiger', 'Yakult', 'Pnhom'] as $n) {
            Brand::firstOrCreate(['slug' => Str::slug($n)], ['name' => $n, 'is_active' => true]);
        }
        $piece = Unit::firstOrCreate(['name' => 'Piece'], ['short_name' => 'pcs', 'is_active' => true]);
        $box = Unit::firstOrCreate(['name' => 'Box'], ['short_name' => 'box', 'is_active' => true]);
        $carton = Unit::firstOrCreate(['name' => 'Carton'], ['short_name' => 'ctn', 'is_active' => true]);
        $pack = Unit::firstOrCreate(['name' => 'Pack'], ['short_name' => 'pack', 'is_active' => true]);
        $bottle = Unit::firstOrCreate(['name' => 'Bottle'], ['short_name' => 'btl', 'is_active' => true]);
        $can = Unit::firstOrCreate(['name' => 'Can'], ['short_name' => 'can', 'is_active' => true]);

        $vat = Tax::firstOrCreate(['name' => 'VAT 10%'], ['rate' => 10, 'is_active' => true]);

        $brands = Brand::all()->keyBy('name');
        $products = [
            // [name, sku, cat, brand, buy, sell]
            ['Coca-Cola Can 330ml', 'COKE-CAN330', 'Beverages', 'Coca-Cola', 1800, 2500],
            ['Pepsi Bottle 500ml', 'PEP-BTL500', 'Beverages', 'Pepsi', 1900, 2700],
            ['ABC Soy Milk 250ml', 'ABC-SOY250', 'Beverages', 'ABC', 2000, 3000],
            ['Anchor Milk 1L', 'ANCH-MILK1L', 'Dairy', 'Anchor', 6500, 8200],
            ['Anchor Condensed Milk', 'ANCH-COND', 'Dairy', 'Anchor', 4500, 5800],
            ['Yakult 5-pack', 'YAK-5PK', 'Dairy', 'Yakult', 5500, 7000],
            ['Nestle Milo 400g', 'NES-MILO400', 'Beverages', 'Nestle', 12000, 15500],
            ['Tiger Beer Can 330ml', 'TIG-CAN330', 'Beverages', 'Tiger', 2800, 3800],
            ['Oishi Chips Original 55g', 'OIS-CHIP55', 'Snacks', 'Oishi', 2200, 3200],
            ['Oishi Prawn Crackers', 'OIS-PRAWN', 'Snacks', 'Oishi', 2500, 3500],
            ['Mama Noodles Shrimp 60g', 'MAMA-SHR60', 'Noodles & Rice', 'Mama', 1500, 2200],
            ['Mama Noodles Pork 60g', 'MAMA-PORK60', 'Noodles & Rice', 'Mama', 1500, 2200],
            ['Jasmine Rice 5kg', 'PNH-RICE5K', 'Noodles & Rice', 'Pnhom', 28000, 34000],
            ['Jasmine Rice 10kg', 'PNH-RICE10K', 'Noodles & Rice', 'Pnhom', 54000, 65000],
            ['Fish Sauce Pnhom 750ml', 'PNH-FISH750', 'Noodles & Rice', 'Pnhom', 5500, 7200],
            ['Soy Sauce ABC 600ml', 'ABC-SOY600', 'Noodles & Rice', 'ABC', 4800, 6300],
            ['Nestle Coffee 200g', 'NES-COF200', 'Beverages', 'Nestle', 15000, 18500],
            ['Pepsi Can 330ml x6', 'PEP-6PK', 'Beverages', 'Pepsi', 15000, 18000],
            ['Coca-Cola Bottle 1.5L', 'COKE-15L', 'Beverages', 'Coca-Cola', 4500, 6000],
            ['Baby Diapers M 40pcs', 'PNH-DIAPM40', 'Baby Care', 'Pnhom', 32000, 39000],
            ['Baby Wipes 80pcs', 'PNH-WIPE80', 'Baby Care', 'Pnhom', 8000, 10500],
            ['Detergent Powder 800g', 'PNH-DET800', 'Household', 'Pnhom', 9500, 12000],
            ['Dish Soap 500ml', 'PNH-DISH500', 'Household', 'Pnhom', 4500, 6000],
            ['Frozen Shrimp 500g', 'PNH-SHR500', 'Frozen', 'Pnhom', 22000, 28000],
            ['Frozen Fish Balls 500g', 'PNH-FISHB500', 'Frozen', 'Pnhom', 12000, 15500],
            ['Ice Cream Cup 100ml', 'NES-ICE100', 'Frozen', 'Nestle', 3000, 4500],
        ];
        // pad to ~60 with variants
        $extraCats = array_keys($cats);
        $extraBrands = ['Coca-Cola', 'Pepsi', 'ABC', 'Anchor', 'Nestle', 'Oishi', 'Mama', 'Tiger', 'Yakult', 'Pnhom'];
        $i = count($products);
        $k = 0;
        while ($i < 60) {
            $k++;
            $c = $extraCats[$k % count($extraCats)];
            $b = $extraBrands[$k % count($extraBrands)];
            $buy = 1000 + (($k * 1373) % 20000);
            $products[] = ["Market Item {$c} {$k}", 'MKT-'.str_pad((string) (1000 + $k), 4, '0', STR_PAD_LEFT), $c, $b, $buy, (int) ($buy * 1.3)];
            $i++;
        }

        $barcodeSeq = 885000001000;
        foreach ($products as $idx => [$name, $sku, $cat, $brand, $buy, $sell]) {
            $p = Product::firstOrCreate(['sku' => $sku], [
                'name' => $name, 'category_id' => $cats[$cat]->id ?? null,
                'brand_id' => $brands[$brand]->id ?? null, 'unit_id' => $piece->id,
                'purchase_price' => $buy, 'sale_price' => $sell, 'tax_rate' => 10,
                'type' => 'standard', 'is_active' => true,
            ]);
            $base = ProductUnit::firstOrCreate(['product_id' => $p->id, 'unit_id' => $piece->id], [
                'conversion_qty' => 1, 'sale_price' => $sell, 'barcode' => (string) ($barcodeSeq++), 'is_base' => true,
            ]);
            // 1-2 extra units
            if ($idx % 2 === 0) {
                $bulkUnit = in_array($cat, ['Beverages']) ? $carton : $box;
                $conv = in_array($cat, ['Beverages']) ? 24 : 12;
                ProductUnit::firstOrCreate(['product_id' => $p->id, 'unit_id' => $bulkUnit->id], [
                    'conversion_qty' => $conv, 'sale_price' => (int) ($sell * $conv * 0.95), 'barcode' => (string) ($barcodeSeq++), 'is_base' => false,
                ]);
            }
            if ($idx % 3 === 0) {
                ProductTax::firstOrCreate(['product_id' => $p->id, 'tax_id' => $vat->id]);
            }
            if ($idx % 5 === 0) {
                ProductBarcode::firstOrCreate(['barcode' => 'KH'.(900000 + $idx)], ['product_id' => $p->id, 'product_unit_id' => $base->id]);
            }
            $qty = $idx % 9 === 0 ? ($idx % 5) + 1 : (5 + (($idx * 37) % 495)); // some <=5 low stock
            Stock::firstOrCreate(['warehouse_id' => $warehouse->id, 'product_unit_id' => $base->id], ['quantity' => $qty]);
        }

        // --- Suppliers ---
        $allProducts = Product::all();
        $baseUnitOf = fn ($p) => ProductUnit::where('product_id', $p->id)->where('is_base', true)->first()
            ?? ProductUnit::where('product_id', $p->id)->first();
        $supNames = ['Angkor Distributor', 'Phnom Penh Foods', 'Mekong Trading', 'Sokha Supply', 'Vattanac Goods', 'Kampot Spices Co', 'Tonle Wholesale', 'City Mart Supply'];
        $suppliers = [];
        foreach ($supNames as $sIdx => $sName) {
            $code = 'SUP-'.str_pad((string) ($sIdx + 2), 3, '0', STR_PAD_LEFT);
            $s = Supplier::firstOrCreate(['code' => $code], [
                'name' => $sName, 'email' => Str::slug($sName).'@example.com',
                'phone' => '0'.(10 + $sIdx).' '.fake()->numerify('### ###'), 'status' => 'active',
            ]);
            SupplierContact::firstOrCreate(['supplier_id' => $s->id, 'name' => 'Contact '.$sName], [
                'phone' => '0'.(31 + $sIdx).' '.fake()->numerify('### ###'), 'position' => 'Sales Rep',
            ]);
            SupplierAddress::firstOrCreate(['supplier_id' => $s->id, 'address' => fake()->streetAddress()], [
                'label' => 'Warehouse', 'city' => 'Phnom Penh', 'country' => 'KH', 'is_default' => true,
            ]);
            foreach ($allProducts->random(min(8, $allProducts->count())) as $rp) {
                SupplierProduct::firstOrCreate(['supplier_id' => $s->id, 'product_id' => $rp->id], ['cost_price' => $rp->purchase_price]);
            }
            $suppliers[] = $s;
        }

        // --- Purchase orders (continue PO- sequence; demo had none, start PO-0001) ---
        $poSeq = (PurchaseOrder::max('id') ?? 0) + 1;
        for ($po = 0; $po < 3; $po++) {
            $s = $suppliers[$po % count($suppliers)];
            $poNo = 'PO-'.str_pad((string) ($poSeq + $po), 4, '0', STR_PAD_LEFT);
            $order = PurchaseOrder::firstOrCreate(['po_no' => $poNo], [
                'supplier_id' => $s->id, 'warehouse_id' => $warehouse->id, 'branch_id' => $branch->id,
                'order_date' => now()->subDays(20 - $po * 5)->toDateString(), 'status' => 'received',
                'subtotal' => 0, 'grand_total' => 0, 'paid_amount' => 0,
            ]);
            if ($order->items()->count() === 0) {
                $sub = 0;
                foreach ($allProducts->random(6) as $rp) {
                    $qty = fake()->numberBetween(10, 60);
                    $line = $qty * $rp->purchase_price;
                    $sub += $line;
                    PurchaseItem::create(['purchase_order_id' => $order->id, 'product_id' => $rp->id,
                        'product_unit_id' => $baseUnitOf($rp)?->id, 'quantity' => $qty, 'unit_cost' => $rp->purchase_price, 'line_total' => $line]);
                }
                $order->update(['subtotal' => $sub, 'grand_total' => $sub, 'paid_amount' => $po === 2 ? (int) ($sub / 2) : $sub]);
                $pay = SupplierPayment::firstOrCreate(['reference' => $poNo], [
                    'supplier_id' => $s->id, 'payment_method_id' => $khqr->id,
                    'amount' => $order->paid_amount, 'payment_date' => now()->subDays(18 - $po * 5)->toDateString(),
                ]);
            }
        }

        // --- Customers ---
        $retail = CustomerGroup::firstOrCreate(['name' => 'Retail'], ['discount_percent' => 0]);
        $wholesale = CustomerGroup::firstOrCreate(['name' => 'Wholesale'], ['discount_percent' => 5]);
        $vip = CustomerGroup::firstOrCreate(['name' => 'VIP'], ['discount_percent' => 10]);
        $groups = [$retail, $wholesale, $vip];
        $customers = Customer::whereNotIn('code', ['CUST-WALKIN', 'CUST-001'])->get()->keyBy('code');
        for ($c = 1; $c <= 25; $c++) {
            $code = 'CUST-'.str_pad((string) (100 + $c), 4, '0', STR_PAD_LEFT);
            if (! isset($customers[$code])) {
                $g = $groups[$c % 3];
                $cust = Customer::firstOrCreate(['code' => $code], [
                    'name' => fake()->name(), 'phone' => '0'.fake()->numerify('## ### ####'),
                    'customer_group_id' => $g->id, 'status' => 'active',
                ]);
                CustomerAddress::firstOrCreate(['customer_id' => $cust->id, 'address' => fake()->streetAddress()], [
                    'label' => 'Home', 'city' => 'Phnom Penh', 'is_default' => true,
                ]);
                CustomerAccount::firstOrCreate(['customer_id' => $cust->id], ['balance' => 0, 'credit_limit' => $g->name === 'Retail' ? 0 : 2000000]);
                $customers[$code] = $cust;
            }
        }

        // --- Cash registers/sessions ---
        $reg2 = CashRegister::firstOrCreate(['code' => 'REG-02'], ['branch_id' => $branch->id, 'name' => 'Counter 2', 'is_active' => true]);
        $reg3 = CashRegister::firstOrCreate(['code' => 'REG-03'], ['branch_id' => $branch->id, 'name' => 'Counter 3', 'is_active' => true]);
        if ($cashier) {
            CashSession::firstOrCreate(['cash_register_id' => $reg2->id, 'user_id' => $cashier->id, 'status' => 'open'],
                ['opening_balance' => 200000, 'opened_at' => now()]);
            $closed = CashSession::firstOrCreate(['cash_register_id' => $reg3->id, 'user_id' => $cashier->id, 'status' => 'closed'],
                ['opening_balance' => 150000, 'closing_balance' => 850000, 'opened_at' => now()->subDays(2), 'closed_at' => now()->subDays(1)]);
        }

        // --- Sales (INV- sequence continuing) ---
        $invSeq = (Sale::max('id') ?? 0) + 1;
        $custList = Customer::all();
        $session = CashSession::where('status', 'open')->first();
        $sales = [];
        for ($si = 0; $si < 15; $si++) {
            $inv = 'INV-'.str_pad((string) ($invSeq + $si), 5, '0', STR_PAD_LEFT);
            $sale = Sale::where('invoice_no', $inv)->first();
            if (! $sale) {
                $cust = $custList->random();
                $items = $allProducts->random(fake()->numberBetween(2, 5));
                $sub = 0;
                $lines = [];
                foreach ($items as $rp) {
                    $qty = fake()->numberBetween(1, 6);
                    $line = $qty * $rp->sale_price;
                    $sub += $line;
                    $lines[] = ['product_id' => $rp->id, 'product_unit_id' => $baseUnitOf($rp)?->id, 'quantity' => $qty, 'unit_price' => $rp->sale_price, 'line_total' => $line];
                }
                $partial = $si % 4 === 3;
                $paid = $partial ? (int) ($sub * 0.5) : $sub;
                $sale = Sale::create(['invoice_no' => $inv, 'branch_id' => $branch->id, 'customer_id' => $cust->id,
                    'user_id' => $cashier?->id, 'cash_session_id' => $session?->id, 'status' => 'completed',
                    'subtotal' => $sub, 'grand_total' => $sub, 'paid_amount' => $paid,
                    'created_at' => now()->subDays($si % 14), 'updated_at' => now()->subDays($si % 14)]);
                foreach ($lines as $ln) {
                    SaleItem::create(array_merge(['sale_id' => $sale->id], $ln));
                }
                Payment::create(['sale_id' => $sale->id, 'payment_method_id' => ($si % 2 ? $khqr : $cash)->id, 'amount' => $paid, 'reference' => $inv]);
                if ($partial) {
                    CustomerPayment::create(['customer_id' => $cust->id, 'payment_method_id' => $cash->id, 'amount' => $paid, 'payment_date' => now()->subDays($si % 14)->toDateString(), 'reference' => $inv]);
                }
            }
            $sales[] = $sale;
        }

        // --- Returns ---
        foreach (array_slice($sales, 0, 3) as $rIdx => $s) {
            $retNo = 'RET-'.str_pad((string) ($rIdx + 1), 4, '0', STR_PAD_LEFT);
            $ret = SalesReturn::firstOrCreate(['return_no' => $retNo], [
                'sale_id' => $s->id, 'customer_id' => $s->customer_id, 'status' => 'approved', 'total' => 0,
            ]);
            if ($ret->items()->count() === 0) {
                $firstItem = $s->items()->first();
                if ($firstItem) {
                    $total = $firstItem->unit_price * 1;
                    SalesReturnItem::create(['sales_return_id' => $ret->id, 'sale_item_id' => $firstItem->id,
                        'product_id' => $firstItem->product_id, 'quantity' => 1, 'unit_price' => $firstItem->unit_price, 'line_total' => $total]);
                    $ret->update(['total' => $total]);
                    SalesReturnRefund::firstOrCreate(['sales_return_id' => $ret->id], ['payment_method_id' => $cash->id, 'amount' => $total]);
                }
            }
        }

        // --- Discounts / taxes / expenses ---
        Discount::firstOrCreate(['name' => 'Khmer New Year 10%'], ['type' => 'percentage', 'value' => 10, 'is_active' => true]);
        Discount::firstOrCreate(['name' => 'Weekend 5%'], ['type' => 'percentage', 'value' => 5, 'is_active' => true]);
        $expCats = [];
        foreach (['Rent', 'Utilities', 'Salaries', 'Transport', 'Packaging', 'Maintenance', 'Marketing', 'Supplies', 'Internet', 'Misc'] as $ec) {
            $expCats[] = ExpenseCategory::firstOrCreate(['name' => $ec], ['is_active' => true]);
        }
        for ($e = 0; $e < 20; $e++) {
            Expense::firstOrCreate(
                ['expense_category_id' => $expCats[$e % count($expCats)]->id, 'expense_date' => now()->subDays($e % 30)->toDateString(), 'amount' => 50000 + $e * 7000],
                ['branch_id' => $branch->id, 'notes' => fake()->sentence()]
            );
        }

        // --- Delivery ---
        $zones = [];
        foreach ([['Phnom Penh', 4000], ['Toul Kork', 5000], ['Chbar Ampov', 6000], ['Outside PP', 12000]] as [$zn, $fee]) {
            $zones[] = DeliveryZone::firstOrCreate(['name' => $zn], ['fee' => $fee, 'is_active' => true]);
        }
        $d1 = DeliveryDriver::firstOrCreate(['name' => 'Dara Sok', 'phone' => '012 345 678'], ['vehicle' => 'Moto', 'status' => 'active']);
        $d2 = DeliveryDriver::firstOrCreate(['name' => 'Vicheka Chan', 'phone' => '098 765 432'], ['vehicle' => 'Tuk-tuk', 'status' => 'active']);
        $drivers = [$d1, $d2];
        $statuses = ['pending', 'assigned', 'delivering', 'delivered', 'delivered', 'delivered', 'cancelled', 'delivered'];
        foreach ($custList->take(8)->values() as $dIdx => $cust) {
            $dno = 'DO-'.str_pad((string) ($dIdx + 1), 4, '0', STR_PAD_LEFT);
            $zone = $zones[$dIdx % count($zones)];
            DeliveryAddress::firstOrCreate(['customer_id' => $cust->id, 'address' => fake()->streetAddress()], [
                'delivery_zone_id' => $zone->id, 'phone' => $cust->phone, 'is_default' => true,
            ]);
            DeliveryOrder::firstOrCreate(['delivery_no' => $dno], [
                'sale_id' => $sales[$dIdx % count($sales)]->id ?? null, 'customer_id' => $cust->id,
                'delivery_driver_id' => $drivers[$dIdx % 2]->id, 'delivery_zone_id' => $zone->id,
                'status' => $statuses[$dIdx], 'fee' => $zone->fee, 'address' => fake()->streetAddress(),
            ]);
        }

        // --- Settings ---
        Setting::firstOrCreate(['branch_id' => null, 'key' => 'currency'], ['value' => 'KHR']);
        Setting::firstOrCreate(['branch_id' => null, 'key' => 'tax_rate'], ['value' => '10']);
        Setting::firstOrCreate(['branch_id' => $branch->id, 'key' => 'low_stock_threshold'], ['value' => '5']);

        // touch employee check: ensure at least one driver-linked employee exists
        Employee::firstOrCreate(['first_name' => 'Dara', 'last_name' => 'Sok'], ['branch_id' => $branch->id, 'position' => 'Driver', 'status' => 'active']);
    }
}
