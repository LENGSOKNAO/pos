<?php

use App\Models\ProductUnit;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\PosDemoSeeder;

test('create purchase order increases stock', function () {
    $this->seed(PosDemoSeeder::class);
    $user = User::where('username', 'admin')->firstOrFail();
    $supplier = Supplier::firstOrFail();
    $warehouse = Warehouse::firstOrFail();
    $productUnit = ProductUnit::firstOrFail();

    $before = (float) (Stock::where('warehouse_id', $warehouse->id)->where('product_unit_id', $productUnit->id)->first()?->quantity ?? 0);

    $response = $this->actingAs($user)->post('/purchases', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'items' => [
            ['product_unit_id' => $productUnit->id, 'quantity' => 5, 'unit_cost' => 2.5],
        ],
    ]);

    $response->assertRedirect(route('purchases.index'));
    expect(PurchaseOrder::count())->toBe(1)
        ->and(PurchaseOrder::first()->po_no)->toStartWith('PO-');

    $after = (float) Stock::where('warehouse_id', $warehouse->id)->where('product_unit_id', $productUnit->id)->firstOrFail()->quantity;
    expect($after)->toBeGreaterThan($before);
});
