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

        // Always allow requests to the unlock routes themselves to avoid redirect loops.
        if ($request->is($routePrefix) || $request->is($routePrefix.'/*')) {
            return $next($request);
        }

        if ($request->session()->get($sessionKey) === true) {
            return $next($request);
        }

        // Store the intended URL so we can redirect back after successful unlock.
        $request->session()->put('access_lock_intended', $request->fullUrl());

        return redirect()->route('access-lock.show');
    }
}
