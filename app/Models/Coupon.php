<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'coupons';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'promotion_id',
        'code',
        'usage_limit',
        'used_count',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'promotion_id' => 'string',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function isValid(): bool
    {
        return $this->status === 'active'
            && $this->used_count < $this->usage_limit
            && $this->promotion->isActive();
    }
}
