<?php

namespace AlvinFadli\AccessLock\Tests\Feature;

use AlvinFadli\AccessLock\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class MiddlewareTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        // Include 'web' so StartSession runs before our middleware.
        $router->get('/protected', fn () => response('Protected content'))
            ->middleware(['web', 'access.lock']);

        $router->get('/open', fn () => response('Open content'));
    }

    // -------------------------------------------------------------------------
    // Locked state — middleware returns view directly (no redirect)
    // -------------------------------------------------------------------------

    public function test_locked_request_returns_200_with_prompt_view(): void
    {
        $this->get('/protected')
            ->assertOk()
            ->assertSee('access-lock-form');
    }

    public function test_locked_request_embeds_intended_url_in_form(): void
    {
        $this->get('/protected')
            ->assertOk()
            ->assertSee('http://localhost/protected', false);
    }

    public function test_unprotected_route_is_accessible_without_session(): void
    {
        $this->get('/open')
            ->assertOk()
            ->assertSee('Open content');
    }

    // -------------------------------------------------------------------------
    // Unlocked state
    // -------------------------------------------------------------------------

    public function test_unlocked_session_passes_through_to_protected_route(): void
    {
        $this->withSession(['access_lock_unlocked' => true])
            ->get('/protected')
            ->assertOk()
            ->assertSee('Protected content');
    }

    public function test_session_value_must_be_boolean_true_to_pass_through(): void
    {
        $this->withSession(['access_lock_unlocked' => 1])
            ->get('/protected')
            ->assertOk()
            ->assertSee('access-lock-form');

        $this->withSession(['access_lock_unlocked' => 'yes'])
            ->get('/protected')
            ->assertOk()
            ->assertSee('access-lock-form');
    }

    // -------------------------------------------------------------------------
    // Unlock POST route passthrough (avoid loops)
    // -------------------------------------------------------------------------

    public function test_post_unlock_route_is_not_intercepted_by_middleware(): void
    {
        $this->app['config']->set('access-lock.password_hash', Hash::make('secret'));

        $this->post('/access-lock', ['password' => 'secret', 'intended' => '/'])
            ->assertRedirect('/');
    }

    // -------------------------------------------------------------------------
    // Error flash is forwarded to the view
    // -------------------------------------------------------------------------

    public function test_middleware_forwards_error_flash_to_view(): void
    {
        $this->withSession(['access_lock_error' => 'Incorrect password. Please try again.'])
            ->get('/protected')
            ->assertOk()
            ->assertSee('Incorrect password');
    }

    // -------------------------------------------------------------------------
    // Custom session key
    // -------------------------------------------------------------------------

    public function test_middleware_respects_custom_session_key(): void
    {
        $this->app['config']->set('access-lock.session_key', 'my_custom_unlock_key');

        $this->withSession(['my_custom_unlock_key' => true])
            ->get('/protected')
            ->assertOk()
            ->assertSee('Protected content');
    }

    // -------------------------------------------------------------------------
    // Query param bypass (presence only — any non-empty value counts)
    // -------------------------------------------------------------------------

    public function test_query_param_present_bypasses_lock_and_passes_through(): void
    {
        $this->app['config']->set('access-lock.bypass.query', ['ssoKey']);

        $this->get('/protected?ssoKey=anything')
            ->assertOk()
            ->assertSee('Protected content');
    }

    public function test_all_required_query_params_must_be_present(): void
    {
        $this->app['config']->set('access-lock.bypass.query', ['ssoKey', 'userId']);

        // Only one of the two keys present — should still show prompt.
        $this->get('/protected?ssoKey=abc')
            ->assertOk()
            ->assertSee('access-lock-form');

        // Both keys present — should bypass.
        $this->get('/protected?ssoKey=abc&userId=123')
            ->assertOk()
            ->assertSee('Protected content');
    }

    public function test_query_param_bypass_permanently_unlocks_session(): void
    {
        $this->app['config']->set('access-lock.bypass.query', ['ssoKey']);

        // First request with the param — unlocks the session.
        $this->get('/protected?ssoKey=anything')->assertOk();

        // Subsequent request WITHOUT the param — session is still unlocked.
        $this->get('/protected')
            ->assertOk()
            ->assertSee('Protected content');
    }

    public function test_missing_query_param_shows_prompt(): void
    {
        $this->app['config']->set('access-lock.bypass.query', ['ssoKey']);

        $this->get('/protected')
            ->assertOk()
            ->assertSee('access-lock-form');
    }

    public function test_empty_query_bypass_config_does_not_bypass(): void
    {
        $this->app['config']->set('access-lock.bypass.query', []);

        $this->get('/protected?ssoKey=anything')
            ->assertOk()
            ->assertSee('access-lock-form');
    }

    // -------------------------------------------------------------------------
    // Header bypass (presence only — any non-empty value counts)
    // -------------------------------------------------------------------------

    public function test_header_present_bypasses_lock_and_passes_through(): void
    {
        $this->app['config']->set('access-lock.bypass.headers', ['X-SSO-Key']);

        $this->withHeaders(['X-SSO-Key' => 'anything'])
            ->get('/protected')
            ->assertOk()
            ->assertSee('Protected content');
    }

    public function test_header_bypass_permanently_unlocks_session(): void
    {
        $this->app['config']->set('access-lock.bypass.headers', ['X-SSO-Key']);

        // First request with the header — unlocks the session.
        $this->withHeaders(['X-SSO-Key' => 'anything'])
            ->get('/protected')
            ->assertOk();

        // Subsequent request WITHOUT the header — session is still unlocked.
        $this->get('/protected')
            ->assertOk()
            ->assertSee('Protected content');
    }

    public function test_missing_header_shows_prompt(): void
    {
        $this->app['config']->set('access-lock.bypass.headers', ['X-SSO-Key']);

        $this->get('/protected')
            ->assertOk()
            ->assertSee('access-lock-form');
    }
}
