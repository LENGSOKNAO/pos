<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
}
