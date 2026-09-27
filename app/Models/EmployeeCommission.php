<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCommission extends Model
{
    use HasFactory;

    protected $table = 'employee_commissions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'employee_id',
        'invoice_id',
        'commission_rate',
        'commission_amount',
    ];

    protected $casts = [
        'id' => 'string',
        'employee_id' => 'string',
        'invoice_id' => 'string',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:4',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
