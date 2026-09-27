<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashSessionController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = CashSession::with('register', 'employee');

        if ($request->has('register_id')) {
            $query->where('register_id', $request->register_id);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('opened_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('opened_at', '<=', $request->date_to);
        }

        $sessions = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginated($sessions);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request, [
            'register_id' => 'required|exists:cash_registers,id',
            'employee_id' => 'required|exists:employees,id',
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $register = CashRegister::findOrFail($data['register_id']);

        // Check if register already has an open session
        $existingSession = CashSession::where('register_id', $register->id)
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            return $this->error('Register already has an open session', 400);
        }

        $session = CashSession::create([
            'register_id' => $data['register_id'],
            'employee_id' => $data['employee_id'],
            'opening_cash' => $data['opening_cash'],
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return $this->success($session->load('register', 'employee'), 'Cash session opened successfully', 201);
    }

    public function show(CashSession $cashSession)
    {
        return $this->success($cashSession->load(['register', 'employee']));
    }

    public function close(Request $request, CashSession $cashSession)
    {
        if ($cashSession->status !== 'open') {
            return $this->error('Session is not open', 400);
        }

        $data = $this->validateRequest($request, [
            'closing_cash' => 'required|numeric|min:0',
        ]);

        // Calculate expected cash from sales, payments, etc.
        $sales = SalesOrder::where('employee_id', $cashSession->employee_id)
            ->where('order_date', '>=', $cashSession->opened_at)
            ->where('status', 'completed')
            ->get();

        $totalSales = $sales->sum('total');
        $totalPayments = Payment::where('received_by', auth()->id())
            ->where('payment_date', '>=', $cashSession->opened_at)
            ->where('status', 'completed')
            ->sum('amount');

        $totalRefunds = Refund::whereHas('salesReturn', function ($q) use ($cashSession) {
            $q->where('branch_id', $cashSession->register->branch_id);
        })
            ->where('refunded_at', '>=', $cashSession->opened_at)
            ->where('status', 'completed')
            ->sum('amount');

        $expectedCash = $cashSession->opening_cash + $totalPayments - $totalRefunds;

        return DB::transaction(function () use ($cashSession, $data, $expectedCash) {
            $difference = $data['closing_cash'] - $expectedCash;

            $cashSession->update([
                'closing_cash' => $data['closing_cash'],
                'expected_cash' => $expectedCash,
                'difference' => $difference,
                'closed_at' => now(),
                'status' => 'closed',
            ]);

            return $this->success($cashSession->load('register', 'employee'), 'Cash session closed successfully');
        });
    }

    public function reconcile(Request $request, CashSession $cashSession)
    {
        if ($cashSession->status !== 'closed') {
            return $this->error('Session must be closed before reconciliation', 400);
        }

        $cashSession->update(['status' => 'reconciled']);

        return $this->success($cashSession, 'Cash session reconciled successfully');
    }
}
