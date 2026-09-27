<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class BankTransactionController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = BankTransaction::with('bankAccount');

        if ($request->has('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->has('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->has('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($transactions);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transaction_type' => 'required|in:deposit,withdrawal,transfer_in,transfer_out,fee,interest',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|string',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
        ]);

        $bankAccount = BankAccount::findOrFail($data['bank_account_id']);

        $balanceAfter = $bankAccount->current_balance;
        if (in_array($data['transaction_type'], ['deposit', 'transfer_in', 'interest'])) {
            $balanceAfter += $data['amount'];
        } elseif (in_array($data['transaction_type'], ['withdrawal', 'transfer_out', 'fee'])) {
            $balanceAfter -= $data['amount'];
        }

        $transaction = BankTransaction::create([
            'bank_account_id' => $data['bank_account_id'],
            'transaction_type' => $data['transaction_type'],
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'amount' => $data['amount'],
            'balance_after' => $balanceAfter,
            'transaction_date' => now(),
            'description' => $data['description'],
        ]);

        $bankAccount->update(['current_balance' => $balanceAfter]);

        return $this->success($transaction->load('bankAccount'), 'Bank transaction recorded successfully', 201);
    }

    public function show(BankTransaction $bankTransaction)
    {
        return $this->success($bankTransaction->load('bankAccount'));
    }
}
