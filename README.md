# access-lock

A lightweight Laravel package that password-protects your application (or specific routes) using a middleware, a JavaScript `prompt()`, and Laravel session storage.

> **Not intended for production-grade authentication.** Use this as a simple access gate — e.g. for staging environments, internal tools, or early-access previews.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.2 |
| Laravel | ^10.0 or ^11.0 |

---

## Installation

### 1. Install via Composer

```bash
composer require alvinfadli/access-lock
```

The service provider is auto-discovered; no manual registration is needed.

### 2. Set a Password

Run the Artisan command to set the access password:

```bash
php artisan access-lock:set-password
```

You will be prompted to enter and confirm a password. The bcrypt hash is automatically written to your `.env` file as:

```
ACCESS_LOCK_PASSWORD_HASH="$2y$12$..."
```

If you cache your configuration, clear it afterwards:

```bash
php artisan config:clear
```

---

## Usage

### Web (Monolith / Blade)

> **Important:** always add this middleware inside the **`web` group**, never in the global middleware stack. The global stack runs before `StartSession`, so `$request->session()` would not be available yet and you would get a *"Session store not set on request"* error.

**Laravel 10 — `app/Http/Kernel.php`**

Add it to the `web` group (after `StartSession`):

```php
protected $middlewareGroups = [
    'web' => [
        // ... existing entries ...
        \AlvinFadli\AccessLock\Http\Middleware\AccessLockMiddleware::class,
    ],
];
```

**Laravel 11 / 12 / 13 — `bootstrap/app.php`**

Use `appendToGroup('web', ...)` — **not** `append()`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', \AlvinFadli\AccessLock\Http\Middleware\AccessLockMiddleware::class);
})
```

---

### API (Decoupled / SPA / Mobile)

For decoupled setups (e.g. Angular, React, Vue, or mobile apps talking to a Laravel API), use the `access.lock.api` middleware instead. It is token-based and returns JSON responses rather than redirecting to a Blade view.

#### 1. Protect your API routes

```php
// routes/api.php
Route::middleware('access.lock.api')->group(function () {
    Route::apiResource('/users', UserController::class);
    // ... other protected routes
});
```

#### 2. Obtain a token

POST the staging password to the built-in unlock endpoint. No authentication is required for this endpoint — it is the entry point.

```
POST /api/access-lock/unlock
Content-Type: application/json

{ "password": "your-staging-password" }
```

**Success (200):**
```json
{ "token": "your-staging-token", "expires_in": 120 }
```

**Wrong password (401):**
```json
{ "message": "Invalid password." }
```

#### 3. Use the token on subsequent requests

Include the token on every protected API request using one of two headers:

```
Authorization: Bearer your-staging-password
```

or

```
X-Access-Lock-Token: your-staging-password
```

The middleware verifies the token against the configured bcrypt hash on every request — no session or cache storage is needed.

#### How it works

1. The client POSTs the password to `/api/access-lock/unlock`.
2. The package verifies it using `access_lock_verify()`.
3. On success, the plain-text password is returned as a token.
4. The client stores the token (e.g. `localStorage`) and sends it with every subsequent request.
5. `AccessLockApiMiddleware` calls `access_lock_verify(token)` on each request — if it matches the configured hash, the request passes through; otherwise it returns `403`.

---

### Protect a Route Group (web)

```php
Route::middleware('access.lock')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/reports', [ReportController::class, 'index']);
});
```

---

### Protect a Single Route (web)

```php
Route::get('/secret', [SecretController::class, 'index'])->middleware('access.lock');
```

---

### Protect a Route Group (api)

```php
Route::middleware('access.lock.api')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/reports', [ReportController::class, 'index']);
});
```

---

### Protect a Single Route (api)

```php
Route::get('/secret', [SecretController::class, 'index'])->middleware('access.lock.api');
```

---

## How It Works (Web)

1. A visitor hits a protected route.
2. `AccessLockMiddleware` checks the Laravel session for `access_lock_unlocked = true`.
3. If not unlocked, the visitor is redirected to `/access-lock`.
4. The unlock page loads and a `window.prompt()` dialog appears automatically.
5. The visitor enters the password and it is submitted via `POST`.
6. If correct, the session flag is set and the visitor is redirected back to the original URL.
7. If incorrect, the unlock page reloads with an error message.

---

## Publishing Assets

### Publish Config

```bash
php artisan vendor:publish --tag=access-lock-config
```

This copies `config/access-lock.php` to your application's `config/` directory so you can customise it.

### Publish Views

```bash
php artisan vendor:publish --tag=access-lock-views
```

This copies the unlock Blade view to `resources/views/vendor/access-lock/` for customisation.

### Publish Everything

```bash
php artisan vendor:publish --tag=access-lock
```

---

## Configuration

After publishing, edit `config/access-lock.php`:

```php
return [
    // Bcrypt hash of the access password (set via Artisan command).
    'password_hash' => env('ACCESS_LOCK_PASSWORD_HASH', null),

    // Session key used to track unlocked state.
    'session_key' => 'access_lock_unlocked',

    // URL prefix for the unlock page routes (/access-lock by default).
    'route_prefix' => 'access-lock',

    // Bypass conditions — see "Bypass Conditions" section below.
    'bypass' => [
        'query'   => [],
        'headers' => [],
    ],
];
```

---

## Bypass Conditions

You can configure query string parameters or request headers that **automatically and permanently unlock the session** for a visitor — no password prompt is shown.

This is useful for automated tools, CI checks, SSO redirects, or any trusted caller that should never see the lock screen.

### Setup

Publish the config and list the query keys / header names you want to act as bypass signals:

```bash
php artisan vendor:publish --tag=access-lock-config
```

```php
// config/access-lock.php
'bypass' => [

    // All listed query keys must be present and non-empty to bypass.
    // e.g. visiting /?ssoKey=anything&userId=123 will unlock the session.
    'query' => [
        'ssoKey',
        'userId',
    ],

    // All listed header names must be present and non-empty to bypass.
    // e.g. sending X-SSO-Key: anything will unlock the session.
    'headers' => [
        'X-SSO-Key',
    ],

],
```

### How it works

- No value matching — only **presence** matters. Any non-empty value for the listed keys is accepted.
- When **all** keys in a group (`query` or `headers`) are present and non-empty, the session is flagged as unlocked and the request passes through.
- On **all subsequent requests** from that visitor, the session flag is already set — the params/headers are no longer required.
- An empty `query` or `headers` array disables that bypass group entirely.

> **Note:** for the API middleware (`access.lock.api`), bypass conditions pass the request through directly without requiring a token — no session is involved.

---

## Helper Functions

The package provides three global helpers:

```php
// Returns true if a password hash has been configured.
access_lock_active(): bool

// Returns true if the current visitor has already unlocked access.
access_lock_unlocked(): bool

// Verifies a plain-text password against the configured hash.
access_lock_verify(string $password): bool
```

---

## Setting Password Programmatically

```php
use AlvinFadli\AccessLock\Support\PasswordManager;

PasswordManager::setPassword('my-plain-text-password');
```

---

## Middleware Reference

| Alias | Class | Use case |
|---|---|---|
| `access.lock` | `AccessLockMiddleware` | Monolith / Blade apps — session-based, redirects to prompt page |
| `access.lock.api` | `AccessLockApiMiddleware` | Decoupled / API apps — token-based, returns JSON |

---

## License

MIT — see [LICENSE](LICENSE).