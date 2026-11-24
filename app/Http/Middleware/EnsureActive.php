<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $inactive = $user && (! $user->is_active || ($user->isStaff() && ! $user->employer?->is_active));
        if ($inactive) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['login' => 'Your account is disabled. Please contact support.']);
        }

        return $next($request);
    }
}
