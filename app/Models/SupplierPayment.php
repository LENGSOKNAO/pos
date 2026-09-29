<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPayment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'supplier_payments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'supplier_id',
        'amount',
        'payment_method_id',
        'payment_date',
        'reference_number',
        'paid_by',
    ];

    protected $casts = [
        'id' => 'string',
        'supplier_id' => 'string',
        'amount' => 'decimal:4',
        'payment_method_id' => 'string',
        'payment_date' => 'datetime',
        'paid_by' => 'string',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
