<?php

use AlvinFadli\AccessLock\Support\PasswordManager;

if (! function_exists('access_lock_active')) {
    /**
     * Determine whether a password hash has been configured for access-lock.
     * Useful for conditionally showing lock indicators in views.
     */
    function access_lock_active(): bool
    {
        return ! empty(config('access-lock.password_hash'));
    }
}

if (! function_exists('access_lock_unlocked')) {
    /**
     * Determine whether the current visitor has already unlocked access.
     */
    function access_lock_unlocked(): bool
    {
        $sessionKey = config('access-lock.session_key', 'access_lock_unlocked');

        return session()->get($sessionKey) === true;
    }
}

if (! function_exists('access_lock_verify')) {
    /**
     * Verify a plain-text password against the configured access-lock hash.
     */
    function access_lock_verify(string $password): bool
    {
        return PasswordManager::verify($password);
    }
}

if (! function_exists('access_lock_api_verify')) {
    /**
     * Verify an API token against the configured static token.
     */
    function access_lock_api_verify(string $token): bool
    {
        $configToken = config('access-lock.api.token');

        return ! empty($configToken) && hash_equals($configToken, $token);
    }
}

