<?php

namespace AlvinFadli\AccessLock\Tests\Unit;

use AlvinFadli\AccessLock\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class HelpersTest extends TestCase
{
    // -------------------------------------------------------------------------
    // access_lock_active()
    // -------------------------------------------------------------------------

    public function test_access_lock_active_returns_false_when_no_hash_configured(): void
    {
        $this->app['config']->set('access-lock.password_hash', null);

        $this->assertFalse(access_lock_active());
    }

    public function test_access_lock_active_returns_false_for_empty_string_hash(): void
    {
        $this->app['config']->set('access-lock.password_hash', '');

        $this->assertFalse(access_lock_active());
    }

    public function test_access_lock_active_returns_true_when_hash_is_configured(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->assertTrue(access_lock_active());
    }

    // -------------------------------------------------------------------------
    // access_lock_unlocked()
    // -------------------------------------------------------------------------

    public function test_access_lock_unlocked_returns_false_when_session_not_set(): void
    {
        // No session manipulation — should default to false.
        $this->assertFalse(access_lock_unlocked());
    }

    public function test_access_lock_unlocked_returns_false_when_session_value_is_false(): void
    {
        session()->put('access_lock_unlocked', false);

        $this->assertFalse(access_lock_unlocked());
    }

    public function test_access_lock_unlocked_returns_true_when_session_flag_is_set(): void
    {
        session()->put('access_lock_unlocked', true);

        $this->assertTrue(access_lock_unlocked());
    }

    public function test_access_lock_unlocked_respects_custom_session_key(): void
    {
        $this->app['config']->set('access-lock.session_key', 'custom_key');
        session()->put('custom_key', true);

        $this->assertTrue(access_lock_unlocked());
    }

    // -------------------------------------------------------------------------
    // access_lock_verify()
    // -------------------------------------------------------------------------

    public function test_access_lock_verify_returns_false_when_no_hash_configured(): void
    {
        $this->app['config']->set('access-lock.password_hash', null);

        $this->assertFalse(access_lock_verify('any'));
    }

    public function test_access_lock_verify_returns_false_for_wrong_password(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('correct'));

        $this->assertFalse(access_lock_verify('wrong'));
    }

    public function test_access_lock_verify_returns_true_for_correct_password(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->assertTrue(access_lock_verify('secret'));
    }
}
