<?php

namespace AlvinFadli\AccessLock\Tests\Unit;

use AlvinFadli\AccessLock\Support\PasswordManager;
use AlvinFadli\AccessLock\Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PasswordManagerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // verify()
    // -------------------------------------------------------------------------

    public function test_verify_returns_false_when_no_hash_is_configured(): void
    {
        $this->app['config']->set('access-lock.password_hash', null);

        $this->assertFalse(PasswordManager::verify('any-password'));
    }

    public function test_verify_returns_false_for_wrong_password(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('correct'));

        $this->assertFalse(PasswordManager::verify('wrong'));
    }

    public function test_verify_returns_false_for_empty_password(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('correct'));

        $this->assertFalse(PasswordManager::verify(''));
    }

    public function test_verify_returns_true_for_correct_password(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->assertTrue(PasswordManager::verify('secret'));
    }

    // -------------------------------------------------------------------------
    // setPassword()
    // -------------------------------------------------------------------------

    public function test_set_password_throws_when_env_file_does_not_exist(): void
    {
        $this->app->useEnvironmentPath('/nonexistent-directory-'.uniqid('', true));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Could not locate .env file/');

        PasswordManager::setPassword('secret');
    }

    public function test_set_password_returns_a_bcrypt_hash(): void
    {
        $envFile = $this->setUpTempEnvFile("APP_NAME=Test\n");

        $hash = PasswordManager::setPassword('my-password');

        $this->assertTrue(Hash::check('my-password', $hash));

        unlink($envFile);
        rmdir(dirname($envFile));
    }

    public function test_set_password_appends_entry_when_key_does_not_exist(): void
    {
        $envFile = $this->setUpTempEnvFile("APP_NAME=Test\n");

        PasswordManager::setPassword('secret');

        $contents = file_get_contents($envFile);
        $this->assertStringContainsString('ACCESS_LOCK_PASSWORD_HASH=', $contents);

        unlink($envFile);
        rmdir(dirname($envFile));
    }

    public function test_set_password_replaces_existing_entry(): void
    {
        $oldHash = Hash::make('old-password');
        $envFile = $this->setUpTempEnvFile("APP_NAME=Test\nACCESS_LOCK_PASSWORD_HASH=\"{$oldHash}\"\n");

        PasswordManager::setPassword('new-password');

        $contents = file_get_contents($envFile);

        // The old hash should no longer appear as the value.
        $newHash = null;
        preg_match('/^ACCESS_LOCK_PASSWORD_HASH="(.+)"$/m', $contents, $matches);
        $newHash = $matches[1] ?? null;

        $this->assertNotNull($newHash);
        $this->assertTrue(Hash::check('new-password', $newHash));
        $this->assertFalse(Hash::check('old-password', $newHash));

        // Only one occurrence of the key should exist.
        $this->assertSame(1, substr_count($contents, 'ACCESS_LOCK_PASSWORD_HASH='));

        unlink($envFile);
        rmdir(dirname($envFile));
    }

    public function test_set_password_preserves_other_env_entries(): void
    {
        $envFile = $this->setUpTempEnvFile("APP_NAME=MyApp\nAPP_ENV=local\n");

        PasswordManager::setPassword('secret');

        $contents = file_get_contents($envFile);
        $this->assertStringContainsString('APP_NAME=MyApp', $contents);
        $this->assertStringContainsString('APP_ENV=local', $contents);

        unlink($envFile);
        rmdir(dirname($envFile));
    }
}
