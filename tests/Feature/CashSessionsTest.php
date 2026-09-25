<?php

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use Database\Seeders\PosDemoSeeder;

test('open and close cash session', function () {
    $this->seed(PosDemoSeeder::class);
    $user = User::where('username', 'admin')->firstOrFail();
    $register = CashRegister::firstOrFail();

    $this->actingAs($user)->post('/cash-sessions/open', [
        'cash_register_id' => $register->id,
        'opening_balance' => 100,
    ])->assertRedirect(route('cash-sessions.index'));

    $session = CashSession::where('cash_register_id', $register->id)->where('status', 'open')->firstOrFail();

    $this->actingAs($user)->post("/cash-sessions/{$session->id}/close", [
        'closing_balance' => 120,
    ])->assertRedirect(route('cash-sessions.index'));

    expect($session->fresh()->status)->toBe('closed');
});
