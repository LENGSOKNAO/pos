<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'stock_adjustments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'warehouse_id',
        'adjustment_number',
        'reason',
        'status',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'id' => 'string',
        'warehouse_id' => 'string',
        'created_by' => 'string',
        'approved_by' => 'string',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }
}
