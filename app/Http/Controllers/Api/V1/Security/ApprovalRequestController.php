<?php

namespace App\Http\Controllers\Api\V1\Security;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\ApprovalRequest;
use Illuminate\Http\Request;

class ApprovalRequestController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = ApprovalRequest::with('company', 'branch', 'requester', 'approver');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('request_type')) {
            $query->where('request_type', $request->request_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('requested_by')) {
            $query->where('requested_by', $request->requested_by);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $requests = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($requests);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'request_type' => 'required|string|in:refund,discount,price_override,stock_adjustment,purchase_order,expense,credit,supplier_payment,cash_withdrawal,invoice_cancel',
            'reference_id' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $approvalRequest = ApprovalRequest::create([
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'],
            'request_type' => $data['request_type'],
            'reference_id' => $data['reference_id'],
            'requested_by' => auth()->id(),
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return $this->success($approvalRequest->load('company', 'branch', 'requester'), 'Approval request created successfully', 201);
    }

    public function show(ApprovalRequest $approvalRequest)
    {
        return $this->success($approvalRequest->load('company', 'branch', 'requester', 'approver'));
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        if ($approvalRequest->status !== 'pending') {
            return $this->error('Request is not pending approval', 400);
        }

        $approvalRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this->success($approvalRequest->load('company', 'branch', 'requester', 'approver'), 'Approval request approved successfully');
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        if ($approvalRequest->status !== 'pending') {
            return $this->error('Request is not pending approval', 400);
        }

        $data = $this->validateRequest($request, [
            'reason' => 'nullable|string',
        ]);

        $approvalRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this->success($approvalRequest->load('company', 'branch', 'requester', 'approver'), 'Approval request rejected successfully');
    }
}
