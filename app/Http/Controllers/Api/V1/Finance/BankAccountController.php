<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = BankAccount::with('company', 'branch');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('bank_name')->paginate($request->get('per_page', 15));

        return $this->paginated($accounts);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'currency' => 'required|string|size:3',
            'opening_balance' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        $account = BankAccount::create([
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'],
            'bank_name' => $data['bank_name'],
            'account_name' => $data['account_name'],
            'account_number' => $data['account_number'],
            'currency' => $data['currency'],
            'opening_balance' => $data['opening_balance'] ?? 0,
            'current_balance' => $data['opening_balance'] ?? 0,
            'status' => $data['status'],
        ]);

        return $this->success($account->load('company', 'branch'), 'Bank account created successfully', 201);
    }

    public function show(BankAccount $bankAccount)
    {
        return $this->success($bankAccount->load(['company', 'branch', 'transactions']));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'sometimes|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'bank_name' => 'sometimes|string|max:255',
            'account_name' => 'sometimes|string|max:255',
            'account_number' => 'sometimes|string|max:100',
            'currency' => 'sometimes|string|size:3',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $bankAccount->update($data);

        return $this->success($bankAccount->load('company', 'branch'), 'Bank account updated successfully');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return $this->success(null, 'Bank account deleted successfully');
    }

    public function reconcile(Request $request, BankAccount $bankAccount)
    {
        $data = $this->validateRequest($request, [
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
        ]);

        return $this->success([
            'bank_account' => $bankAccount,
            'statement_date' => $data['statement_date'],
            'statement_balance' => $data['statement_balance'],
            'book_balance' => $bankAccount->current_balance,
            'difference' => $data['statement_balance'] - $bankAccount->current_balance,
        ]);
    }
}
