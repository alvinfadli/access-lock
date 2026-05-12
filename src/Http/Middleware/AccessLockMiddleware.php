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

        // Check query-param bypass: all listed keys must be present and non-empty.
        $queryKeys = config('access-lock.bypass.query', []);
        if (! empty($queryKeys) && $this->allPresent($queryKeys, fn ($k) => $request->query($k))) {
            $request->session()->put($sessionKey, true);
            return $next($request);
        }

        // Check header bypass: all listed header names must be present and non-empty.
        $headerKeys = config('access-lock.bypass.headers', []);
        if (! empty($headerKeys) && $this->allPresent($headerKeys, fn ($k) => $request->header($k))) {
            $request->session()->put($sessionKey, true);
            return $next($request);
        }

        // Return the prompt view directly — no redirect needed.
        // The current URL is embedded in the form so we can come back after unlock.
        return response()->view('access-lock::unlock', [
            'intended' => $request->fullUrl(),
            'error'    => $request->session()->pull('access_lock_error'),
        ]);
    }

    /**
     * Return true when every key in $keys resolves to a non-empty value via $resolver.
     *
     * @param  string[]  $keys
     */
    private function allPresent(array $keys, callable $resolver): bool
    {
        foreach ($keys as $key) {
            if (empty($resolver($key))) {
                return false;
            }
        }

        return true;
    }
}
