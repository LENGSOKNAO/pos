<?php

namespace App\Models;

use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'id',
        'company_id',
        'branch_id',
        'employee_code',
        'first_name',
        'last_name',
        'phone',
        'email',
        'position',
        'hire_date',
        'salary',
        'commission_rate',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'branch_id' => 'string',
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function managedBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'manager_id');
    }

    public function managedWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'manager_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
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

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function invoicesCreated(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
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

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function paymentsReceived(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    public function expensesCreated(): HasMany
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function expensesApproved(): HasMany
    {
        return $this->hasMany(Expense::class, 'approved_by');
    }

    public function employeeAttendance(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function employeeCommissions(): HasMany
    {
        return $this->hasMany(EmployeeCommission::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'delivery_employee_id');
    }

    public function quotationsCreated(): HasMany
    {
        return $this->hasMany(Quotation::class, 'created_by');
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
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }
}
