<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sales_return_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'sales_return_id',
        'product_id',
        'quantity',
        'unit_price',
        'refund_amount',
    ];

    protected $casts = [
        'id' => 'string',
        'sales_return_id' => 'string',
        'product_id' => 'string',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'refund_amount' => 'decimal:4',
    ];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
