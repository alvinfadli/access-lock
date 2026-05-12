<?php

namespace AlvinFadli\AccessLock\Console\Commands;

use AlvinFadli\AccessLock\Support\PasswordManager;
use Illuminate\Console\Command;
use RuntimeException;

class SetAccessLockPasswordCommand extends Command
{
    protected $signature = 'access-lock:set-password
                            {--password= : The plain-text password to set (skips interactive prompt)}';

    protected $description = 'Set the access-lock password (stored as a bcrypt hash in .env)';

    public function handle(): int
    {
        $password = $this->option('password');

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

        try {
            $hash = PasswordManager::setPassword($password);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Access-lock password set successfully.');
        $this->line('');
        $this->line('Your .env file update, run: php artisan config:clear:');
        $this->line('  <info>ACCESS_LOCK_PASSWORD_HASH="'.$hash.'"</info>');
        $this->line('');

        return self::SUCCESS;
    }
}
