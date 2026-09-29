<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReceiptItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'purchase_receipt_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'receipt_id',
        'product_id',
        'batch_id',
        'quantity',
        'unit_cost',
    ];

    protected $casts = [
        'id' => 'string',
        'receipt_id' => 'string',
        'product_id' => 'string',
        'batch_id' => 'string',
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }
}
