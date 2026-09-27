<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use Illuminate\Http\Request;

class LoyaltyTransactionController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = LoyaltyTransaction::with('customer', 'invoice');

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($transactions);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'points' => 'required|numeric',
            'transaction_type' => 'required|in:earn,redeem,expire,adjust',
        ]);

        $customer = Customer::findOrFail($data['customer_id']);

        $transaction = LoyaltyTransaction::create([
            'customer_id' => $data['customer_id'],
            'invoice_id' => $data['invoice_id'],
            'points' => $data['points'],
            'transaction_type' => $data['transaction_type'],
        ]);

        // Update customer loyalty points
        if ($data['transaction_type'] === 'earn') {
            $customer->increment('loyalty_points', $data['points']);
        } elseif ($data['transaction_type'] === 'redeem') {
            $customer->decrement('loyalty_points', $data['points']);
        } elseif ($data['transaction_type'] === 'adjust') {
            $customer->increment('loyalty_points', $data['points']); // can be negative
        }

        return $this->success($transaction->load('customer', 'invoice'), 'Loyalty transaction recorded successfully', 201);
    }

    public function show(LoyaltyTransaction $loyaltyTransaction)
    {
        return $this->success($loyaltyTransaction->load('customer', 'invoice'));
    }
}
