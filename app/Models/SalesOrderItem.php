<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $table = 'sales_order_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'sales_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'tax',
        'cost_price',
        'total',
    ];

    protected $casts = [
        'id' => 'string',
        'sales_order_id' => 'string',
        'product_id' => 'string',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount' => 'decimal:4',
        'tax' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getProfitAttribute(): float
    {
        return ($this->unit_price - $this->cost_price) * $this->quantity;
    }
}
