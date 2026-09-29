<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'promotions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'company_id',
        'name',
        'type',
        'value',
        'start_date',
        'end_date',
        'minimum_amount',
        'maximum_discount',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'value' => 'decimal:4',
        'minimum_amount' => 'decimal:4',
        'maximum_discount' => 'decimal:4',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(PromotionProduct::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function isActive(): bool
    {
        $now = now();

        return $this->status === 'active'
            && (! $this->start_date || $this->start_date <= $now)
            && (! $this->end_date || $this->end_date >= $now);
    }
}
