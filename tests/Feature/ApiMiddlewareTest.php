<?php

namespace AlvinFadli\AccessLock\Tests\Feature;

use AlvinFadli\AccessLock\Tests\TestCase;

class ApiMiddlewareTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/api/protected', fn () => response()->json(['data' => 'secret']))
            ->middleware('access.lock.api');

        $router->get('/api/open', fn () => response()->json(['data' => 'public']));
    }

    // -------------------------------------------------------------------------
    // No token — 401
    // -------------------------------------------------------------------------

    public function test_request_without_token_returns_401(): void
    {
        $this->getJson('/api/protected')
            ->assertStatus(401)
            ->assertJson(['message' => 'Access token required.']);
    }

    // -------------------------------------------------------------------------
    // Invalid token — 403
    // -------------------------------------------------------------------------

    public function test_request_with_invalid_bearer_token_returns_403(): void
    {
        $this->app['config']->set('access-lock.api.token', 'correct-token');

        $this->getJson('/api/protected', ['Authorization' => 'Bearer wrong-token'])
            ->assertStatus(403)
            ->assertJson(['message' => 'Invalid or expired access token.']);
    }

    public function test_request_with_invalid_header_token_returns_403(): void
    {
        $this->app['config']->set('access-lock.api.token', 'correct-token');

        $this->getJson('/api/protected', ['X-Access-Lock-Token' => 'wrong-token'])
            ->assertStatus(403)
            ->assertJson(['message' => 'Invalid or expired access token.']);
    }

    // -------------------------------------------------------------------------
    // Valid static token
    // -------------------------------------------------------------------------

    public function test_valid_bearer_token_passes_through(): void
    {
        $this->app['config']->set('access-lock.api.token', 'my-secret-token');

        $this->getJson('/api/protected', ['Authorization' => 'Bearer my-secret-token'])
            ->assertOk()
            ->assertJson(['data' => 'secret']);
    }

    public function test_valid_header_token_passes_through(): void
    {
        $this->app['config']->set('access-lock.api.token', 'my-secret-token');

        $this->getJson('/api/protected', ['X-Access-Lock-Token' => 'my-secret-token'])
            ->assertOk()
            ->assertJson(['data' => 'secret']);
    }

    public function test_empty_config_token_returns_403(): void
    {
        $this->app['config']->set('access-lock.api.token', null);

        $this->getJson('/api/protected', ['Authorization' => 'Bearer anything'])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Unprotected route
    // -------------------------------------------------------------------------

    public function test_unprotected_route_is_accessible_without_token(): void
    {
        $this->getJson('/api/open')
            ->assertOk()
            ->assertJson(['data' => 'public']);
    }

    // -------------------------------------------------------------------------
    // Bypass conditions
    // -------------------------------------------------------------------------

    public function test_query_param_bypass_passes_through(): void
    {
        $this->app['config']->set('access-lock.bypass.query', ['ssoKey']);

        $this->getJson('/api/protected?ssoKey=anything')
            ->assertOk()
            ->assertJson(['data' => 'secret']);
    }

    public function test_header_bypass_passes_through(): void
    {
        $this->app['config']->set('access-lock.bypass.headers', ['X-SSO-Key']);

        $this->getJson('/api/protected', ['X-SSO-Key' => 'anything'])
            ->assertOk()
            ->assertJson(['data' => 'secret']);
    }
}
