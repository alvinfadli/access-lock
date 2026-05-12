<?php

namespace AlvinFadli\AccessLock\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessLockMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = config('access-lock.session_key', 'access_lock_unlocked');
        $routePrefix = config('access-lock.route_prefix', 'access-lock');

        // Always let the unlock POST route through to avoid loops.
        if ($request->is($routePrefix) || $request->is($routePrefix.'/*')) {
            return $next($request);
        }

        if ($request->session()->get($sessionKey) === true) {
            return $next($request);
        }

        // Return the prompt view directly — no redirect needed.
        // The current URL is embedded in the form so we can come back after unlock.
        return response()->view('access-lock::unlock', [
            'intended' => $request->fullUrl(),
            'error'    => $request->session()->pull('access_lock_error'),
        ]);
    }
}
