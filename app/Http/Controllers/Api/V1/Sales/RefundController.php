<?php

namespace App\Http\Controllers\Api\V1\Sales;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CustomerPayment;
use App\Models\Refund;
use App\Models\SalesReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Refund::with(['salesReturn', 'paymentMethod', 'refunder', 'approver']);

        if ($request->has('sales_return_id')) {
            $query->where('sales_return_id', $request->sales_return_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('refunded_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('refunded_at', '<=', $request->date_to);
        }

        $refunds = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($refunds);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'sales_return_id' => 'required|exists:sales_returns,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string',
        ]);

        $salesReturn = SalesReturn::findOrFail($data['sales_return_id']);

        if ($salesReturn->status !== 'approved') {
            return $this->error('Sales return must be approved before refund', 400);
        }

        if ($data['amount'] > $salesReturn->refund_amount) {
            return $this->error('Refund amount cannot exceed return amount', 400);
        }

        // Check if there are already refunds for this return
        $totalRefunded = $salesReturn->refunds()->where('status', 'completed')->sum('amount');
        if (($totalRefunded + $data['amount']) > $salesReturn->refund_amount) {
            return $this->error('Total refund amount cannot exceed return amount', 400);
        }

        $refund = Refund::create([
            'sales_return_id' => $data['sales_return_id'],
            'payment_method_id' => $data['payment_method_id'],
            'amount' => $data['amount'],
            'reference_number' => $data['reference_number'] ?? 'REF-'.now()->format('YmdHis').'-'.rand(1000, 9999),
            'refunded_by' => auth()->id(),
            'status' => 'pending',
            'refunded_at' => now(),
        ]);

        return $this->success($refund->load('salesReturn', 'paymentMethod'), 'Refund created successfully', 201);
    }

    public function show(Refund $refund)
    {
        return $this->success($refund->load(['salesReturn', 'paymentMethod', 'refunder', 'approver']));
    }

    public function approve(Request $request, Refund $refund)
    {
        if ($refund->status !== 'pending') {
            return $this->error('Refund is not pending approval', 400);
        }

        return DB::transaction(function () use ($refund) {
            // Create customer payment record
            $customerPayment = CustomerPayment::create([
                'customer_id' => $refund->salesReturn->customer_id,
                'invoice_id' => $refund->salesReturn->invoice_id,
                'amount' => $refund->amount,
                'payment_method_id' => $refund->payment_method_id,
                'payment_date' => now(),
                'reference_number' => $refund->reference_number,
                'received_by' => auth()->id(),
            ]);

            $refund->update([
                'status' => 'completed',
                'approved_by' => auth()->id(),
            ]);

            return $this->success($refund->load('salesReturn', 'paymentMethod'), 'Refund approved successfully');
        });
    }

    public function reject(Request $request, Refund $refund)
    {
        if ($refund->status !== 'pending') {
            return $this->error('Refund is not pending approval', 400);
        }

        $refund->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return $this->success($refund, 'Refund rejected successfully');
    }
}
