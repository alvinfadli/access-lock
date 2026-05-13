<?php

namespace AlvinFadli\AccessLock\Tests\Feature;

use AlvinFadli\AccessLock\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class ApiUnlockRouteTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_missing_password_returns_422(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->postJson(route('access-lock.api.unlock'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_empty_password_returns_422(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->postJson(route('access-lock.api.unlock'), ['password' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------------------------
    // Wrong password
    // -------------------------------------------------------------------------

    public function test_wrong_password_returns_401(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->postJson(route('access-lock.api.unlock'), ['password' => 'wrong'])
            ->assertStatus(401)
            ->assertJson(['message' => 'Invalid password.']);
    }

    public function test_no_hash_configured_returns_401(): void
    {
        $this->app['config']->set('access-lock.password_hash', null);

        $this->postJson(route('access-lock.api.unlock'), ['password' => 'anything'])
            ->assertStatus(401)
            ->assertJson(['message' => 'Invalid password.']);
    }

    // -------------------------------------------------------------------------
    // Correct password
    // -------------------------------------------------------------------------

    public function test_correct_password_returns_200_with_token(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->postJson(route('access-lock.api.unlock'), ['password' => 'secret'])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_token_is_generated_after_successful_unlock(): void
    {
        $this->app['config']->set(
            'access-lock.password_hash',
            Hash::make('secret')
        );

        $response = $this->postJson(
            route('access-lock.api.unlock'),
            ['password' => 'secret']
        );

        $response->assertOk();

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('secret', $token);
        $this->assertEquals(64, strlen($token));
    }

    public function test_returned_token_can_be_used_to_access_protected_api_route(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        // Register a protected route inline
        $this->app['router']->get('/api/protected', fn () => response()->json(['data' => 'ok']))
            ->middleware('access.lock.api');

        $token = $this->postJson(route('access-lock.api.unlock'), ['password' => 'secret'])
            ->json('token');

        $this->withToken($token)
            ->getJson('/api/protected')
            ->assertOk()
            ->assertJson(['data' => 'ok']);
    }
}