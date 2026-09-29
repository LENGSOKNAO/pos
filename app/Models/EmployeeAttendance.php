<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'employee_attendance';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'employee_id',
        'branch_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'employee_id' => 'string',
        'branch_id' => 'string',
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getWorkHoursAttribute(): float
    {
        if ($this->clock_in && $this->clock_out) {
            return $this->clock_in->floatDiffInHours($this->clock_out);
        }

        return 0;
    }
}
