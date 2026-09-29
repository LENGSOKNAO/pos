<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'delivery_orders';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'sales_order_id',
        'customer_id',
        'delivery_employee_id',
        'address',
        'delivery_fee',
        'status',
        'delivered_at',
    ];

    protected $casts = [
        'id' => 'string',
        'sales_order_id' => 'string',
        'customer_id' => 'string',
        'delivery_employee_id' => 'string',
        'delivery_fee' => 'decimal:4',
        'delivered_at' => 'datetime',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delivery_employee_id');
    }
}
