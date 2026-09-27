<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory;

    protected $table = 'stock';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'product_id',
        'warehouse_id',
        'location_id',
        'quantity',
        'reserved_quantity',
        'damaged_quantity',
        'average_cost',
    ];

    protected $casts = [
        'id' => 'string',
        'product_id' => 'string',
        'warehouse_id' => 'string',
        'location_id' => 'string',
        'quantity' => 'decimal:4',
        'reserved_quantity' => 'decimal:4',
        'damaged_quantity' => 'decimal:4',
        'average_cost' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function getAvailableQuantityAttribute(): float
    {
        return $this->quantity - $this->reserved_quantity - $this->damaged_quantity;
    }

    public function getTotalValueAttribute(): float
    {
        return $this->quantity * $this->average_cost;
    }
}
