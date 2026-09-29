<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankTransaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'bank_transactions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'bank_account_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'amount',
        'balance_after',
        'transaction_date',
        'description',
    ];

    protected $casts = [
        'id' => 'string',
        'bank_account_id' => 'string',
        'reference_id' => 'string',
        'amount' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'transaction_date' => 'datetime',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
