<?php

namespace AlvinFadli\AccessLock\Console\Commands;

use AlvinFadli\AccessLock\Support\PasswordManager;
use Illuminate\Console\Command;
use RuntimeException;

class SetAccessLockPasswordCommand extends Command
{
    protected $signature = 'access-lock:set-password
                            {--password= : The plain-text password to set (skips interactive prompt)} {--ttl= : API token TTL in minutes}';

    protected $description = 'Set the access-lock password (stored as a bcrypt hash in .env)';

    public function handle(): int
    {
        $password = $this->option('password');
        $ttl = $this->option('ttl');

        if (empty($password)) {
            $password = $this->secret('Enter new access-lock password');

            if (empty($password)) {
                $this->error('Password cannot be empty.');
                return self::FAILURE;
            }

            $confirm = $this->secret('Confirm password');

            if ($password !== $confirm) {
                $this->error('Passwords do not match.');
                return self::FAILURE;
            }
        }

        if (empty($ttl)) {
            $ttl = $this->ask('Enter API token TTL in minutes (optional, default: 7200)') ?? 7200;
        }

        if ($ttl !== null) {
            if (! is_numeric($ttl) || (int) $ttl <= 0) {
                $this->error('TTL must be a positive integer.');

                return self::FAILURE;
            }

            $ttl = (int) $ttl;
        }

        try {
            $hash = PasswordManager::setPassword($password);
            if ($ttl !== null) {
                PasswordManager::setEnvValue(
                    'ACCESS_LOCK_API_TOKEN_TTL',
                    (int) $ttl
                );
            }
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Access-lock password set successfully.');
        $this->line('');
        $this->line('Your .env file update, run: php artisan config:clear:');
        $this->line('  <info>ACCESS_LOCK_PASSWORD_HASH="'.$hash.'"</info>');
        
        if ($ttl) {
            $this->line('  <info>ACCESS_LOCK_API_TOKEN_TTL="'.$ttl.'"</info>');
        }
        
        $this->line('');

        return self::SUCCESS;
    }
}
