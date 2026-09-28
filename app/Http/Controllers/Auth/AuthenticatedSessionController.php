<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        try {
            $attempted = Auth::attempt($credentials, $request->boolean('remember'));
        } catch (QueryException $e) {
            // Serverless cold starts against Neon can drop the first DB
            // connection. Retry once on a fresh connection; anything else
            // (or a second failure) still throws and becomes a 500.
            if (! $this->isConnectionFailure($e)) {
                Log::error('login-attempt-failed '.get_class($e).': '.substr($e->getMessage(), 0, 200));

                throw $e;
            }

            Log::warning('login-db-retry after connection failure');
            DB::disconnect();

            try {
                $attempted = Auth::attempt($credentials, $request->boolean('remember'));
            } catch (\Throwable $retry) {
                Log::error('login-retry-failed '.get_class($retry).': '.substr($retry->getMessage(), 0, 200));

                throw $retry;
            }
        } catch (\Throwable $e) {
            Log::error('login-attempt-failed '.get_class($e).': '.substr($e->getMessage(), 0, 200));

            throw $e;
        }

        if (! $attempted) {
            return back()->withErrors([
                'username' => 'These credentials do not match our records.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();

        try {
            $request->user()->forceFill(['last_login' => now()])->save();
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function isConnectionFailure(QueryException $e): bool
    {
        $previous = $e->getPrevious();

        $code = $previous?->getCode();

        if ($code === 7 || in_array((string) $code, ['08000', '08001', '08003', '08004', '08006', '08007', '08P01', '57P01', '57P03'], true)) {
            return true;
        }

        return str_contains(strtolower($e->getMessage()), 'could not connect')
            || str_contains(strtolower($e->getMessage()), 'server closed the connection');
    }
}
