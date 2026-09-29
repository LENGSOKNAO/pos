<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'stock_transfers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_number',
        'status',
        'created_by',
        'approved_by',
        'transferred_at',
    ];

    protected $casts = [
        'id' => 'string',
        'from_warehouse_id' => 'string',
        'to_warehouse_id' => 'string',
        'created_by' => 'string',
        'approved_by' => 'string',
        'transferred_at' => 'datetime',
    ];

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
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
        return $this->hasMany(StockTransferItem::class);
    }
}
