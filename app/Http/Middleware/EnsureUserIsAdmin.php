<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 404 rather than 403, matching the policies' Response::denyAsNotFound() —
        // the admin area doesn't advertise its existence to non-admins.
        if ($user === null || ! $user->isAdmin()) {
            abort(404);
        }

        return $next($request);
    }
}
