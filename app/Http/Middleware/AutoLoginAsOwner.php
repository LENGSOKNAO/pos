<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AutoLoginAsOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            try {
                $owner = User::where('username', 'owner')->first()
                    ?? User::orderBy('id')->first();
            } catch (\Throwable) {
                $owner = null;
            }

            if ($owner) {
                // Stateless: no session write, so it works even when
                // the session store/cookies are broken on serverless.
                Auth::setUser($owner);
            }
        }

        return $next($request);
    }
}
