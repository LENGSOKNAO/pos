<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionProduct extends Model
{
    use HasFactory;

    protected $table = 'promotion_products';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'promotion_id',
        'product_id',
    ];

    protected $casts = [
        'id' => 'string',
        'promotion_id' => 'string',
        'product_id' => 'string',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
