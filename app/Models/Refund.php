<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'refunds';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'sales_return_id',
        'payment_method_id',
        'amount',
        'reference_number',
        'refunded_by',
        'approved_by',
        'refunded_at',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'sales_return_id' => 'string',
        'payment_method_id' => 'string',
        'amount' => 'decimal:4',
        'refunded_by' => 'string',
        'approved_by' => 'string',
        'refunded_at' => 'datetime',
    ];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function refunder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
