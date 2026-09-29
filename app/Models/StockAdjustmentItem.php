<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_adjustment_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'adjustment_id',
        'product_id',
        'quantity_before',
        'quantity_after',
        'difference',
        'reason',
    ];

    protected $casts = [
        'id' => 'string',
        'adjustment_id' => 'string',
        'product_id' => 'string',
        'quantity_before' => 'decimal:4',
        'quantity_after' => 'decimal:4',
        'difference' => 'decimal:4',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
