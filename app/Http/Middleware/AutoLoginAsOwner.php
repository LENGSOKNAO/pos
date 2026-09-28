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
            $owner = User::where('username', 'owner')->first()
                ?? User::orderBy('id')->first();

            if ($owner) {
                Auth::login($owner);
            }
        }

        return $next($request);
    }
}
