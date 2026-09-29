<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeShift extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'employee_shifts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'employee_id',
        'branch_id',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'employee_id' => 'string',
        'branch_id' => 'string',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getDurationHoursAttribute(): float
    {
        return $this->start_time->floatDiffInHours($this->end_time);
    }
}
