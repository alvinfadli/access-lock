<?php

namespace AlvinFadli\AccessLock\Tests\Feature;

use AlvinFadli\AccessLock\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class UnlockRouteTest extends TestCase
{
    // -------------------------------------------------------------------------
    // POST /access-lock — correct password
    // -------------------------------------------------------------------------

    public function test_correct_password_sets_session_flag(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->post(route('access-lock.unlock'), [
            'password' => 'secret',
            'intended' => '/',
        ]);

        $this->assertTrue(session('access_lock_unlocked'));
    }

    public function test_correct_password_redirects_to_intended_url(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->post(route('access-lock.unlock'), [
            'password' => 'secret',
            'intended' => 'http://localhost/dashboard',
        ])->assertRedirect('http://localhost/dashboard');
    }

    public function test_correct_password_redirects_to_home_when_no_intended_url(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->post(route('access-lock.unlock'), [
            'password' => 'secret',
        ])->assertRedirect('/');
    }

    // -------------------------------------------------------------------------
    // POST /access-lock — wrong / missing password
    // -------------------------------------------------------------------------

    public function test_wrong_password_does_not_set_session_flag(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->post(route('access-lock.unlock'), [
            'password' => 'wrong',
            'intended' => '/',
        ]);

        $this->assertNotTrue(session('access_lock_unlocked'));
    }

    public function test_wrong_password_redirects_to_intended_url_with_error(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->post(route('access-lock.unlock'), [
            'password' => 'wrong',
            'intended' => 'http://localhost/dashboard',
        ])
            ->assertRedirect('http://localhost/dashboard')
            ->assertSessionHas('access_lock_error', 'Incorrect password. Please try again.');
    }

    public function test_empty_password_redirects_with_error(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->post(route('access-lock.unlock'), [
            'password' => '',
            'intended' => '/',
        ])
            ->assertRedirect('/')
            ->assertSessionHas('access_lock_error');
    }

    public function test_no_hash_configured_always_rejects(): void
    {
        $this->app['config']->set('access-lock.password_hash', null);

        $this->post(route('access-lock.unlock'), [
            'password' => 'anything',
            'intended' => '/',
        ])
            ->assertRedirect('/')
            ->assertSessionHas('access_lock_error');
    }
}
