<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'email', 'password_hash', 'employee_id', 'last_login', 'status'])]
#[Hidden(['password_hash', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'employee_id' => 'string',
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->using(UserRole::class)
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->using(RolePermission::class)
            ->withTimestamps();
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class, 'employee_id');
    }

    public function paymentsReceived(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    public function stockAdjustmentsCreated(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'created_by');
    }

    public function stockAdjustmentsApproved(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'approved_by');
    }

    public function stockTransfersCreated(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'created_by');
    }

    public function stockTransfersApproved(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'approved_by');
    }

    public function purchaseOrdersCreated(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'created_by');
    }

    public function purchaseOrdersApproved(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'approved_by');
    }

    public function purchaseReceiptsReceived(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class, 'received_by');
    }

    public function salesReturnsCreated(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'created_by');
    }

    public function salesReturnsApproved(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'approved_by');
    }

    public function refundsRefunded(): HasMany
    {
        return $this->hasMany(Refund::class, 'refunded_by');
    }

    public function refundsApproved(): HasMany
    {
        return $this->hasMany(Refund::class, 'approved_by');
    }

    public function expensesCreated(): HasMany
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function expensesApproved(): HasMany
    {
        return $this->hasMany(Expense::class, 'approved_by');
    }

    public function journalEntriesCreated(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'created_by');
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'requested_by');
    }

    public function approvalRequestsApproved(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'approved_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function documentsCreated(): HasMany
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('code', $permission))
            ->exists();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }
}
