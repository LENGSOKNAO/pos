<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::user();

        if (! $user) {
            return $response;
        }

        $shouldAudit = $this->shouldAudit($request, $response);

        if ($shouldAudit) {
            $this->logAudit($request, $response, $user);
        }

        return $response;
    }

    protected function shouldAudit(Request $request, $response): bool
    {
        $auditableMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        $auditablePaths = [
            'api/v1/products',
            'api/v1/invoices',
            'api/v1/sales-orders',
            'api/v1/purchase-orders',
            'api/v1/stock',
            'api/v1/users',
            'api/v1/roles',
            'api/v1/permissions',
            'api/v1/cash-sessions',
            'api/v1/payments',
            'api/v1/refunds',
            'api/v1/expenses',
            'api/v1/approval-requests',
        ];

        if (! in_array($request->method(), $auditableMethods)) {
            return false;
        }

        foreach ($auditablePaths as $path) {
            if (str_starts_with($request->path(), $path)) {
                return true;
            }
        }

        return false;
    }

    protected function logAudit(Request $request, $response, $user): void
    {
        try {
            $oldValues = null;
            $newValues = null;

            if (in_array($request->method(), ['PUT', 'PATCH'])) {
                $oldValues = $this->getOldValues($request);
                $newValues = $request->all();
            } elseif ($request->method() === 'POST') {
                $newValues = $request->all();
            } elseif ($request->method() === 'DELETE') {
                $oldValues = $this->getOldValues($request);
            }

            AuditLog::create([
                'company_id' => $user->employee?->company_id,
                'user_id' => $user->id,
                'branch_id' => $user->employee?->branch_id,
                'action' => strtolower($request->method()),
                'module' => $this->getModuleFromPath($request->path()),
                'table_name' => $this->getTableNameFromPath($request->path()),
                'record_id' => $this->getRecordId($request),
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error('Audit log failed: '.$e->getMessage());
        }
    }

    protected function getOldValues(Request $request): ?array
    {
        $route = $request->route();
        if ($route && $route->parameter('id')) {
            $modelClass = $this->getModelClassFromPath($request->path());
            if ($modelClass && class_exists($modelClass)) {
                $model = $modelClass::find($route->parameter('id'));

                return $model?->toArray();
            }
        }

        return null;
    }

    protected function getModuleFromPath(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        if (count($segments) >= 2 && $segments[0] === 'api' && $segments[1] === 'v1') {
            return $segments[2] ?? 'unknown';
        }

        return 'unknown';
    }

    protected function getTableNameFromPath(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        if (count($segments) >= 3 && $segments[0] === 'api' && $segments[1] === 'v1') {
            return $segments[2] ?? 'unknown';
        }

        return 'unknown';
    }

    protected function getRecordId(Request $request): ?string
    {
        $route = $request->route();

        return $route?->parameter('id');
    }

    protected function getModelClassFromPath(string $path): ?string
    {
        $segments = explode('/', trim($path, '/'));
        if (count($segments) >= 3 && $segments[0] === 'api' && $segments[1] === 'v1') {
            $resource = $segments[2];
            $modelMap = [
                'products' => 'App\Models\Product',
                'categories' => 'App\Models\Category',
                'brands' => 'App\Models\Brand',
                'units' => 'App\Models\Unit',
                'invoices' => 'App\Models\Invoice',
                'sales-orders' => 'App\Models\SalesOrder',
                'purchase-orders' => 'App\Models\PurchaseOrder',
                'stock' => 'App\Models\Stock',
                'users' => 'App\Models\User',
                'roles' => 'App\Models\Role',
                'permissions' => 'App\Models\Permission',
                'cash-sessions' => 'App\Models\CashSession',
                'payments' => 'App\Models\Payment',
                'refunds' => 'App\Models\Refund',
                'expenses' => 'App\Models\Expense',
                'approval-requests' => 'App\Models\ApprovalRequest',
            ];

            return $modelMap[$resource] ?? null;
        }

        return null;
    }
}
