<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRole extends Pivot
{
    use HasUuids;

    protected $table = 'user_roles';

    protected $fillable = [
        'id',
        'user_id',
        'role_id',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'role_id' => 'string',
    ];
}
