<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CashRegister;
use Illuminate\Http\Request;

class CashRegisterController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = CashRegister::with('branch');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $registers = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return $this->paginated($registers);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:100',
            'terminal_number' => 'required|string|unique:cash_registers,terminal_number|max:50',
            'status' => 'required|in:active,inactive,maintenance',
        ]);

        $register = CashRegister::create($data);

        return $this->success($register->load('branch'), 'Cash register created successfully', 201);
    }

    public function show(CashRegister $cashRegister)
    {
        return $this->success($cashRegister->load(['branch', 'sessions']));
    }

    public function update(Request $request, CashRegister $cashRegister)
    {
        $data = $this->validateRequest($request, [
            'name' => 'sometimes|string|max:100',
            'terminal_number' => 'sometimes|string|unique:cash_registers,terminal_number,'.$cashRegister->id.'|max:50',
            'status' => 'sometimes|in:active,inactive,maintenance',
        ]);

        $cashRegister->update($data);

        return $this->success($cashRegister->load('branch'), 'Cash register updated successfully');
    }

    public function destroy(CashRegister $cashRegister)
    {
        $cashRegister->delete();

        return $this->success(null, 'Cash register deleted successfully');
    }
}
