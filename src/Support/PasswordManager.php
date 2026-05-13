<?php

namespace AlvinFadli\AccessLock\Support;

use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PasswordManager
{
    /**
     * Verify a plain-text password against the stored hash.
     */
    public static function verify(string $plainPassword): bool
    {
        $hash = config('access-lock.password_hash');

        if (empty($hash)) {
            return false;
        }

        return Hash::check($plainPassword, $hash);
    }

    /**
     * Hash a plain-text password and persist it to the application's .env file.
     *
     * @throws RuntimeException When the .env file cannot be located or written.
     */
    public static function setPassword(string $plainPassword): string
    {
        $hash = Hash::make($plainPassword);

        $envPath = self::getEnvPath();

        if (! file_exists($envPath)) {
            throw new RuntimeException("Could not locate .env file at [{$envPath}].");
        }

        $contents = file_get_contents($envPath);

        $key = 'ACCESS_LOCK_PASSWORD_HASH';
        $escapedHash = addslashes($hash);
        $line = "{$key}=\"{$escapedHash}\"";

        if (str_contains($contents, $key.'=')) {
            // Replace existing entry (handles values with or without quotes).
            // preg_replace_callback is used so the replacement is treated as a
            // literal string — bcrypt hashes contain '$2y$12$…' which preg_replace
            // would misinterpret as back-references ($2, $1, …) and corrupt.
            $contents = preg_replace_callback(
                '/^'.$key.'=.*/m',
                static fn () => $line,
                $contents
            );
        } else {
            // Append new entry.
            $contents = rtrim($contents)."\n".$line."\n";
        }

        file_put_contents($envPath, $contents);

        return $hash;
    }

    public static function setEnvValue(string $key, string $value): void
    {
        $envPath = self::getEnvPath();

        if (! file_exists($envPath)) {
            throw new RuntimeException("Could not locate .env file at [{$envPath}].");
        }

        $contents = file_get_contents($envPath);

        $escapedValue = addslashes($value);
        $line = "{$key}=\"{$escapedValue}\"";

        if (str_contains($contents, $key.'=')) {
            // Replace existing entry (handles values with or without quotes).
            // preg_replace_callback is used so the replacement is treated as a
            // literal string — bcrypt hashes contain '$2y$12$…' which preg_replace
            // would misinterpret as back-references ($2, $1, …) and corrupt.
            $contents = preg_replace_callback(
                '/^'.$key.'=.*/m',
                static fn () => $line,
                $contents
            );
        } else {
            // Append new entry.
            $contents = rtrim($contents)."\n".$line."\n";
        }

        file_put_contents($envPath, $contents);
    }

    protected static function getEnvPath(): string
    {
        return app()->environmentFilePath();
    }
}
