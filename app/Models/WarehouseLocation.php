<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseLocation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'warehouse_locations';

    protected $fillable = [
        'id',
        'warehouse_id',
        'code',
        'name',
        'type',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'warehouse_id' => 'string',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
