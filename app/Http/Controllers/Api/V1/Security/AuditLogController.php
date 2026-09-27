<?php

namespace App\Http\Controllers\Api\V1\Security;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user', 'branch');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        if ($request->has('table_name')) {
            $query->where('table_name', $request->table_name);
        }

        if ($request->has('record_id')) {
            $query->where('record_id', $request->record_id);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($logs);
    }

    public function show(AuditLog $auditLog)
    {
        return $this->success($auditLog->load('user', 'branch'));
    }
}
