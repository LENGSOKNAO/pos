<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPayment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'customer_payments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'invoice_id',
        'amount',
        'payment_method_id',
        'payment_date',
        'reference_number',
        'received_by',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'invoice_id' => 'string',
        'amount' => 'decimal:4',
        'payment_method_id' => 'string',
        'payment_date' => 'datetime',
        'received_by' => 'string',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
