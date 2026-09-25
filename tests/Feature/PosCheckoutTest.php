<?php

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\PosDemoSeeder;

test('pos checkout creates sale with items', function () {
    $this->seed(PosDemoSeeder::class);
    $user = User::where('username', 'admin')->firstOrFail();
    $warehouse = Warehouse::firstOrFail();
    $productUnit = ProductUnit::firstOrFail();
    Stock::where('warehouse_id', $warehouse->id)
        ->where('product_unit_id', $productUnit->id)
        ->update(['quantity' => 100]);

    $response = $this->actingAs($user)->post('/pos', [
        'warehouse_id' => $warehouse->id,
        'items' => [
            [
                'product_unit_id' => $productUnit->id,
                'quantity' => 2,
                'unit_price' => 1.5,
            ],
        ],
    ]);

    $response->assertRedirect(route('pos.index'));
    expect(Sale::count())->toBe(1)
        ->and(Sale::first()->items()->count())->toBe(1);
});

test('pos checkout applies customer and order discount', function () {
    $this->seed(PosDemoSeeder::class);
    $user = User::where('username', 'admin')->firstOrFail();
    $warehouse = Warehouse::firstOrFail();
    $productUnit = ProductUnit::firstOrFail();
    Stock::where('warehouse_id', $warehouse->id)
        ->where('product_unit_id', $productUnit->id)
        ->update(['quantity' => 100]);
    $customer = Customer::firstOrFail();

    $response = $this->actingAs($user)->post('/pos', [
        'warehouse_id' => $warehouse->id,
        'customer_id' => $customer->id,
        'discount_amount' => 1,
        'items' => [
            [
                'product_unit_id' => $productUnit->id,
                'quantity' => 2,
                'unit_price' => 1.5,
            ],
        ],
    ]);

    $response->assertRedirect(route('pos.index'))->assertSessionHas('receipt_sale_id');
    $sale = Sale::firstOrFail();
    expect($sale->customer_id)->toBe($customer->id)
        ->and((float) $sale->subtotal)->toBe(3.0)
        ->and((float) $sale->discount_amount)->toBe(1.0)
        ->and((float) $sale->grand_total)->toBe(2.0);
});

test('pos barcode lookup returns matching unit', function () {
    $this->seed(PosDemoSeeder::class);
    $user = User::where('username', 'admin')->firstOrFail();
    $productUnit = ProductUnit::whereNotNull('barcode')->first();
    if ($productUnit === null) {
        $productUnit = ProductUnit::firstOrFail();
        $productUnit->update(['barcode' => 'TEST-BARCODE-001']);
    }

    $response = $this->actingAs($user)->getJson('/pos?barcode='.urlencode($productUnit->barcode));

    $response->assertOk()->assertJson(['product_unit_id' => $productUnit->id]);

    $this->actingAs($user)->getJson('/pos?barcode=NO-SUCH-BARCODE')->assertNotFound();
});

test('pos checkout supports split payments', function () {
    $this->seed(PosDemoSeeder::class);
    $user = User::where('username', 'admin')->firstOrFail();
    $warehouse = Warehouse::firstOrFail();
    $productUnit = ProductUnit::firstOrFail();
    Stock::where('warehouse_id', $warehouse->id)
        ->where('product_unit_id', $productUnit->id)
        ->update(['quantity' => 100]);
    $methods = PaymentMethod::where('is_active', true)->limit(2)->get();
    expect($methods->count())->toBeGreaterThanOrEqual(1);

    $payload = [
        'warehouse_id' => $warehouse->id,
        'discount_type' => 'fixed',
        'discount_amount' => 0,
        'payments' => [
            ['payment_method_id' => $methods[0]->id, 'amount' => 1.5],
        ],
        'items' => [
            ['product_unit_id' => $productUnit->id, 'quantity' => 2, 'unit_price' => 1.5],
        ],
    ];
    if ($methods->count() >= 2) {
        $payload['payments'][] = ['payment_method_id' => $methods[1]->id, 'amount' => 1.5];
    } else {
        $payload['payments'][0]['amount'] = 3.0;
    }

    $this->actingAs($user)->post('/pos', $payload)->assertRedirect(route('pos.index'));

    $sale = Sale::firstOrFail();
    expect((float) $sale->paid_amount)->toBe(3.0)
        ->and($sale->payments()->count())->toBe(count($payload['payments']));
});

test('pos checkout supports percent discount', function () {
    $this->seed(PosDemoSeeder::class);
    $user = User::where('username', 'admin')->firstOrFail();
    $warehouse = Warehouse::firstOrFail();
    $productUnit = ProductUnit::firstOrFail();
    Stock::where('warehouse_id', $warehouse->id)
        ->where('product_unit_id', $productUnit->id)
        ->update(['quantity' => 100]);

    $this->actingAs($user)->post('/pos', [
        'warehouse_id' => $warehouse->id,
        'discount_type' => 'percent',
        'discount_amount' => 10,
        'paid_amount' => 9.0,
        'items' => [
            ['product_unit_id' => $productUnit->id, 'quantity' => 2, 'unit_price' => 5.0],
        ],
    ])->assertRedirect(route('pos.index'));

    $sale = Sale::firstOrFail();
    expect((float) $sale->subtotal)->toBe(10.0)
        ->and((float) $sale->discount_amount)->toBe(1.0)
        ->and((float) $sale->grand_total)->toBe(9.0);
});
