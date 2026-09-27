<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cash_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'register_id',
        'employee_id',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'difference',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'register_id' => 'string',
        'employee_id' => 'string',
        'opening_cash' => 'decimal:4',
        'closing_cash' => 'decimal:4',
        'expected_cash' => 'decimal:4',
        'difference' => 'decimal:4',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function getCalculatedExpectedCashAttribute(): float
    {
        // This would be calculated from sales, payments, cash in/out during the session
        return $this->opening_cash + $this->sales_total - $this->refunds_total + $this->cash_in_total - $this->cash_out_total;
    }
}
