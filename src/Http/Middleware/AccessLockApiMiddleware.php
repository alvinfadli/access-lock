<?php

namespace AlvinFadli\AccessLock\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AccessLockApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $routePrefix = config('access-lock.route_prefix', 'access-lock');

        if ($request->is($routePrefix . '/api/unlock')) {
            return $next($request);
        }

        $queryKeys = config('access-lock.bypass.query', []);
        if (! empty($queryKeys) && $this->allPresent($queryKeys, fn ($k) => $request->query($k))) {
            return $next($request);
        }

        $headerKeys = config('access-lock.bypass.headers', []);
        if (! empty($headerKeys) && $this->allPresent($headerKeys, fn ($k) => $request->header($k))) {
            return $next($request);
        }

        $token = $request->bearerToken() ?? $request->header('X-Access-Lock-Token');

        if (empty($token)) {
            return response()->json(['message' => 'Access token required.'], 401);
        }

        if (! Cache::get("access_lock_api:{$token}")) {
            return response()->json(['message' => 'Invalid or expired access token.'], 403);
        }

        return $next($request);
    }

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