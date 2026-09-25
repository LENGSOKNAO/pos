<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReturnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_flow_restocks_stock(): void
    {
        $user = User::factory()->create(['username' => 'cashier1']);
        $warehouse = Warehouse::create(['name' => 'Main', 'code' => 'WH-1', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $product = Product::create(['name' => 'Test Item', 'sku' => 'T-1', 'sale_price' => 10, 'is_active' => true]);
        $pu = ProductUnit::create(['product_id' => $product->id, 'unit_id' => $unit->id, 'sale_price' => 10]);
        Stock::create(['warehouse_id' => $warehouse->id, 'product_unit_id' => $pu->id, 'quantity' => 5]);

        $sale = Sale::create(['invoice_no' => 'INV-000001', 'status' => 'completed', 'subtotal' => 20, 'grand_total' => 20, 'paid_amount' => 20]);
        $item = SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_unit_id' => $pu->id, 'quantity' => 2, 'unit_price' => 10, 'line_total' => 20]);

        // Simulate POS decrement
        Stock::where('warehouse_id', $warehouse->id)->where('product_unit_id', $pu->id)->first()->decrement('quantity', 2);

        $response = $this->actingAs($user)->post('/sales-returns', [
            'sale_id' => $sale->id,
            'warehouse_id' => $warehouse->id,
            'items' => [['sale_item_id' => $item->id, 'quantity' => 1]],
        ]);

        $response->assertRedirect(route('sales-returns.index'));
        $this->assertDatabaseHas('sales_returns', ['sale_id' => $sale->id, 'total' => 10]);
        $this->assertDatabaseHas('sales_return_refunds', ['amount' => 10]);
        $this->assertEquals(4, (float) Stock::where('warehouse_id', $warehouse->id)->where('product_unit_id', $pu->id)->first()->quantity);
    }
}
