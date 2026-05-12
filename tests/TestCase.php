<?php

namespace AlvinFadli\AccessLock\Tests;

use AlvinFadli\AccessLock\AccessLockServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AccessLockServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('session.driver', 'array');
        $app['config']->set('access-lock.route_prefix', 'access-lock');
        $app['config']->set('access-lock.session_key', 'access_lock_unlocked');
        $app['config']->set('access-lock.password_hash', null);
    }

    /**
     * Create a temporary directory with a .env file, point the app at it,
     * and return the full path to the .env file. Cleaned up automatically
     * via the returned path — callers should unlink in tearDown or after use.
     */
    protected function setUpTempEnvFile(string $initialContents = ''): string
    {
        $tempDir = sys_get_temp_dir().'/access-lock-test-'.uniqid('', true);
        mkdir($tempDir, 0755, true);

        $envFile = $tempDir.'/.env';
        file_put_contents($envFile, $initialContents);

        $this->app->useEnvironmentPath($tempDir);

        return $envFile;
    }
}
