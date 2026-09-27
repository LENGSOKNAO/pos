<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingAccount extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'accounting_accounts';

    protected $fillable = [
        'id',
        'company_id',
        'parent_id',
        'account_code',
        'account_name',
        'account_type',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'parent_id' => 'string',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AccountingAccount::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }
}
