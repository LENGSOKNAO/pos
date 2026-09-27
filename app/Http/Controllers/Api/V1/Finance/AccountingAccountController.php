<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\AccountingAccount;
use Illuminate\Http\Request;

class AccountingAccountController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = AccountingAccount::with('company', 'parent');

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('account_code')->paginate($request->get('per_page', 15));

        return $this->paginated($accounts);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'company_id' => 'required|exists:companies,id',
            'parent_id' => 'nullable|exists:accounting_accounts,id',
            'account_code' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'status' => 'required|in:active,inactive',
        ]);

        $account = AccountingAccount::create($data);

        return $this->success($account->load('company', 'parent'), 'Account created successfully', 201);
    }

    public function show(AccountingAccount $accountingAccount)
    {
        return $this->success($accountingAccount->load(['company', 'parent', 'children', 'journalEntryLines']));
    }

    public function update(Request $request, AccountingAccount $accountingAccount)
    {
        $data = $this->validateRequest($request, [
            'parent_id' => 'nullable|exists:accounting_accounts,id',
            'account_code' => 'sometimes|string|max:50',
            'account_name' => 'sometimes|string|max:255',
            'account_type' => 'sometimes|in:asset,liability,equity,revenue,expense',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $accountingAccount->update($data);

        return $this->success($accountingAccount->load('company', 'parent'), 'Account updated successfully');
    }

    public function destroy(AccountingAccount $accountingAccount)
    {
        $accountingAccount->delete();

        return $this->success(null, 'Account deleted successfully');
    }
}
