<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suspension is enforced here rather than in Fortify::authenticateUsing() on purpose:
 * this app has passkey login enabled, which never runs the password pipeline. Checking
 * on every authenticated request also terminates sessions that were already live when
 * the suspension landed, instead of waiting for the next login.
 */
class EnsureUserIsNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isSuspended()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = __('Your account has been suspended. Please contact an administrator.');

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
