<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\CashTransaction;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CashSessionController extends Controller
{
    public function index(): Response
    {
        $sessions = CashSession::query()
            ->with(['register:id,name,code'])
            ->orderByDesc('id')
            ->paginate(15);

        return Inertia::render('cash-sessions/index', [
            'sessions' => $sessions->through(fn (CashSession $s): array => [
                'id' => $s->id,
                'register' => $s->register?->name ?? $s->register?->code ?? '—',
                'opening_balance' => (float) $s->opening_balance,
                'closing_balance' => $s->closing_balance === null ? null : (float) $s->closing_balance,
                'expected' => $this->expected($s),
                'status' => $s->status,
                'opened_at' => $s->opened_at,
                'closed_at' => $s->closed_at,
            ]),
            'registers' => CashRegister::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cash_register_id' => ['required', 'exists:cash_registers,id'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ]);

        CashSession::create([
            'cash_register_id' => $validated['cash_register_id'],
            'user_id' => $request->user()->id,
            'opening_balance' => $validated['opening_balance'],
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return redirect()->route('cash-sessions.index');
    }

    public function close(Request $request, CashSession $cashSession): RedirectResponse
    {
        $validated = $request->validate([
            'closing_balance' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($cashSession, $validated): void {
            $expected = $this->expected($cashSession);

            $cashSession->update([
                'closing_balance' => $validated['closing_balance'],
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            CashTransaction::create([
                'cash_session_id' => $cashSession->id,
                'type' => 'close',
                'amount' => (float) $validated['closing_balance'] - $expected,
                'notes' => 'Session close. Expected: '.$expected,
            ]);
        });

        return redirect()->route('cash-sessions.index');
    }

    private function expected(CashSession $session): float
    {
        $sales = (float) Sale::where('cash_session_id', $session->id)->sum('paid_amount');
        $tx = (float) CashTransaction::where('cash_session_id', $session->id)->where('type', 'in')->sum('amount')
            - (float) CashTransaction::where('cash_session_id', $session->id)->where('type', 'out')->sum('amount');

        return (float) $session->opening_balance + $sales + $tx;
    }
}
