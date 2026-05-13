<?php

namespace AlvinFadli\AccessLock\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessLockApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check query-param bypass: all listed keys must be present and non-empty.
        $queryKeys = config('access-lock.bypass.query', []);
        if (! empty($queryKeys) && $this->allPresent($queryKeys, fn ($k) => $request->query($k))) {
            return $next($request);
        }

        // Check header bypass: all listed header names must be present and non-empty.
        $headerKeys = config('access-lock.bypass.headers', []);
        if (! empty($headerKeys) && $this->allPresent($headerKeys, fn ($k) => $request->header($k))) {
            return $next($request);
        }

        // Extract the token from Authorization header or X-Access-Lock-Token header.
        $token = $this->resolveToken($request);

        if (empty($token)) {
            return response()->json([
                'message' => 'Access token required.',
            ], 401);
        }

        $configToken = config('access-lock.api.token');

        if (empty($configToken) || ! hash_equals($configToken, $token)) {
            return response()->json([
                'message' => 'Invalid or expired access token.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Extract the token from the request headers.
     */
    protected function resolveToken(Request $request): ?string
    {
        // 1. Check Authorization: Bearer <token>
        $bearer = $request->bearerToken();
        if (! empty($bearer)) {
            return $bearer;
        }

        // 2. Check X-Access-Lock-Token header
        $header = $request->header('X-Access-Lock-Token');
        if (! empty($header)) {
            return $header;
        }

        return null;
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
