<?php

namespace AlvinFadli\AccessLock\Tests\Feature;

use AlvinFadli\AccessLock\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class ApiMiddlewareTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/api/protected', fn () => response()->json(['data' => 'secret']))
            ->middleware('access.lock.api');

        $router->get('/api/open', fn () => response()->json(['data' => 'open']));
    }

    // -------------------------------------------------------------------------
    // No token
    // -------------------------------------------------------------------------

    public function test_request_without_token_returns_401(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->getJson('/api/protected')
            ->assertStatus(401)
            ->assertJson(['message' => 'Access token required.']);
    }

    public function test_unprotected_route_is_accessible_without_token(): void
    {
        $this->getJson('/api/open')
            ->assertOk()
            ->assertJson(['data' => 'open']);
    }

    // -------------------------------------------------------------------------
    // Wrong token
    // -------------------------------------------------------------------------

    public function test_wrong_bearer_token_returns_403(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->withToken('wrong-password')
            ->getJson('/api/protected')
            ->assertStatus(403)
            ->assertJson(['message' => 'Invalid or expired access token.']);
    }

    public function test_wrong_x_access_lock_token_header_returns_403(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->withHeaders(['X-Access-Lock-Token' => 'wrong-password'])
            ->getJson('/api/protected')
            ->assertStatus(403)
            ->assertJson(['message' => 'Invalid or expired access token.']);
    }

    // -------------------------------------------------------------------------
    // Correct token
    // -------------------------------------------------------------------------

    public function test_correct_bearer_token_passes_through(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->withToken('secret')
            ->getJson('/api/protected')
            ->assertOk()
            ->assertJson(['data' => 'secret']);
    }

    public function test_correct_x_access_lock_token_header_passes_through(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->withHeaders(['X-Access-Lock-Token' => 'secret'])
            ->getJson('/api/protected')
            ->assertOk()
            ->assertJson(['data' => 'secret']);
    }

    public function test_bearer_token_takes_precedence_over_x_access_lock_token_header(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        // Bearer is correct, header is wrong — should pass through.
        $this->withToken('secret')
            ->withHeaders(['X-Access-Lock-Token' => 'wrong'])
            ->getJson('/api/protected')
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // No hash configured
    // -------------------------------------------------------------------------

    public function test_no_hash_configured_always_rejects(): void
    {
        $this->app['config']->set('access-lock.password_hash', null);

        $this->withToken('anything')
            ->getJson('/api/protected')
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Query param bypass
    // -------------------------------------------------------------------------

    public function test_query_param_bypass_passes_through_without_token(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));
        $this->app['config']->set('access-lock.bypass.query', ['ssoKey']);

        $this->getJson('/api/protected?ssoKey=anything')
            ->assertOk()
            ->assertJson(['data' => 'secret']);
    }

    public function test_all_required_query_params_must_be_present_for_bypass(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));
        $this->app['config']->set('access-lock.bypass.query', ['ssoKey', 'userId']);

        // Only one key — should reject.
        $this->getJson('/api/protected?ssoKey=abc')
            ->assertStatus(401);

        // Both keys — should pass.
        $this->getJson('/api/protected?ssoKey=abc&userId=123')
            ->assertOk();
    }

    public function test_empty_query_bypass_config_does_not_bypass(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));
        $this->app['config']->set('access-lock.bypass.query', []);

        $this->getJson('/api/protected?ssoKey=anything')
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Header bypass
    // -------------------------------------------------------------------------

    public function test_header_bypass_passes_through_without_token(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));
        $this->app['config']->set('access-lock.bypass.headers', ['X-SSO-Key']);

        $this->withHeaders(['X-SSO-Key' => 'anything'])
            ->getJson('/api/protected')
            ->assertOk()
            ->assertJson(['data' => 'secret']);
    }

    public function test_missing_bypass_header_still_requires_token(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));
        $this->app['config']->set('access-lock.bypass.headers', ['X-SSO-Key']);

        $this->getJson('/api/protected')
            ->assertStatus(401);
    }

    public function test_empty_header_bypass_config_does_not_bypass(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));
        $this->app['config']->set('access-lock.bypass.headers', []);

        $this->withHeaders(['X-SSO-Key' => 'anything'])
            ->getJson('/api/protected')
            ->assertStatus(401);
    }
}