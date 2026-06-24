# Security Improvements — Design Spec

**Date:** June 24, 2026
**Scope:** All critical and high-severity security issues identified in the audit.

---

## Phases

The work is organized into 4 phases with a dependency chain:

```
Phase 0: Quick wins (independent)
    ↓
Phase 1: Middleware pipeline (foundation)
    ↓
Phase 2: CSRF + Auth middleware
    ↓
Phase 3: Security headers + Rate limiting
```

---

## Phase 0 — Quick Wins (independent fixes)

These do not depend on the middleware system and can be done at any time.

| # | Fix | File(s) | Detail |
|---|-----|---------|--------|
| 0.1 | Session fixation | `AuthHelper.php` | Add `session_regenerate_id(true)` after successful login in `AuthHelper::attempt()` |
| 0.2 | SameSite cookie | `Session.php::init()` | Set `ini_set('session.cookie_samesite', 'Lax')` |
| 0.3 | XSS in Error page | `Error.php:91-94` | Wrap exception class, message, file path output in `htmlspecialchars()` |
| 0.4 | SHOW_ERRORS default | `simply-docs/app/Config/global.php` | Change default from `true` to `false` |
| 0.5 | Remove `$_GET` Twig global | `View.php:93` | Remove `$twig->addGlobal('_get', $_GET)` — exposes raw query string to all templates |
| 0.6 | Host header injection | `View.php:70-71` | Sanitize `$_SERVER['HTTP_HOST']` when building `baseurl` global (filter with `parse_url()`, reject if not a valid host) |
| 0.7 | Open redirect | `Request.php:redirect()` | Reject `$url` starting with `http://`, `https://`, or `//` (only allow relative paths) |
| 0.8 | Server-side MIME validation | `FileUpload.php` | Add `finfo()` MIME-type check after upload, comparing against allowed types per extension map |

---

## Phase 1 — Middleware Pipeline

### Core idea

A middleware pipeline wraps controller dispatch. Each middleware gets the Request and a `$next` closure. It can either short-circuit (return early via redirect, error, exit) or pass to `$next($request)`.

### New files

All under `src/Simple/Middleware/`:

| File | Purpose |
|------|---------|
| `Middleware.php` | Interface with `handle(Request $request, Closure $next)` |
| `Pipeline.php` | Chain executor using `array_reduce` |
| `Csrf.php` | CSRF token validation middleware |
| `Auth.php` | Authentication check middleware |
| `SecurityHeaders.php` | Security response headers middleware |
| `RateLimit.php` | Rate limiting middleware |

### Middleware interface

```php
namespace Simple\Middleware;

use Simple\Request;
use Closure;

interface Middleware
{
    public function handle(Request $request, Closure $next);
}
```

### Pipeline execution

```php
namespace Simple\Middleware;

class Pipeline
{
    protected array $middleware = [];

    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    public function send(Request $request): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn($next, $middleware) => fn($request) => (new $middleware)->handle($request, $next),
            fn($request) => null  // terminal — controller dispatch fills this
        );

        return $pipeline($request);
    }
}
```

### Router integration

```php
// Alias registration (in Router or separate config)
Router::middlewareAlias('csrf', Csrf::class);
Router::middlewareAlias('auth', Auth::class);
Router::middlewareAlias('headers', SecurityHeaders::class);
Router::middlewareAlias('rate-limit', RateLimit::class);

// On route groups (inherited by nested routes)
Router::group(['prefix' => 'admin', 'middleware' => ['auth']], function () {
    Router::get('dashboard', 'Admin@dashboard');
});

// On individual routes (fluent)
Router::get('profile', 'User@profile')->middleware('auth');
Router::post('contact', 'Contact@send')->middleware(['csrf', 'rate-limit:5,60']);

// Global middleware — runs on every request
Router::globalMiddleware(['headers']);
```

### Router changes

- `Router.php`: Add `middlewareAlias()`, `globalMiddleware()` static methods. `group()` accepts `middleware` key in options. `Route` object gets a `middleware()` fluent method.
- `BaseRouter.php::dispatch()`: Before calling controller, build a Pipeline from the resolved middleware stack (group + route-specific + global), inject the controller dispatch as the terminal callback.

### Request object changes

- `Request.php`: Add `setMiddleware(string[] $names)` / `getMiddleware()` for metadata. No other behavioral change.

### Middleware resolution order

1. Global middleware (via `Router::globalMiddleware()`)
2. Group middleware (inherited from outermost to innermost group)
3. Route-specific middleware

All run in the order defined. Each `handle()` calls `$next($request)` to continue.

---

## Phase 2 — CSRF + Auth Middleware

### Csrf middleware

- Generates a CSRF token per session on first request (stored in `$_SESSION['_token']`)
- On POST/PUT/PATCH/DELETE: checks `$_POST['_token']` or `$_SERVER['HTTP_X_CSRF_TOKEN']` against the session token using `hash_equals()`
- Skips GET/HEAD/OPTIONS
- Token available to Twig templates as `{{ csrf_field() }}` function (adds hidden input) and `{{ csrf_token() }}` (raw token string)
- On failure: throws `\RuntimeException('CSRF token mismatch')` with 419 status code

### Auth middleware

- Checks `Session::get('user')` — if null, redirects to `/auth/login` with 302, saves current URL to redirect back after login
- If user is set, calls `$next($request)` to continue
- Skips the auth check route itself (prevent redirect loop)

### Twig integration

```php
// In View.php Twig setup:
$twig->addFunction(new \Twig\TwigFunction('csrf_token', function () {
    return $_SESSION['_token'] ?? '';
}));
$twig->addFunction(new \Twig\TwigFunction('csrf_field', function () {
    $token = $_SESSION['_token'] ?? '';
    return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
}));
```

---

## Phase 3 — Security Headers + Rate Limiting

### SecurityHeaders middleware

Runs before controller (sets headers before any body output). Sets response headers:

```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: same-origin
Content-Security-Policy: {CSP_POLICY constant or "default-src 'self'"}
Strict-Transport-Security: max-age=31536000; includeSubDomains  (only if HTTPS)
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

HSTS is skipped when `$_SERVER['HTTPS']` is empty to avoid breaking local HTTP dev. CSP policy configurable via `CSP_POLICY` constant.

### RateLimit middleware

- **Storage:** JSON files in `app/storage/framework/rate-limit/{ip}-{signature}.json`
- **Contents:** `{"attempts": 5, "first_attempt": 1719212345}`
- **Config constants:**
  - `RATE_LIMIT_MAX_ATTEMPTS` (default 5)
  - `RATE_LIMIT_DECAY_SECONDS` (default 60)
- **Behavior:**
  - Increments attempt count on every request to the route
  - If `attempts > max` and `time() - first_attempt < decay`: returns 429 with `Retry-After` header
  - Resets automatically after decay window passes
- **Clear on success:** Route handlers call `RateLimit::clear()` to reset on successful login

**Usage:**

```php
Router::post('auth/authenticate', 'Auth@authenticate')->middleware(['csrf', 'rate-limit:5,60']);
```

Middleware parameters passed as colon-delimited: `rate-limit:5,60` means 5 attempts per 60 seconds.

---

## Files modified

### Framework (src/Simple/)

| File | Change |
|------|--------|
| `Request.php` | Add `setMiddleware()` / `getMiddleware()` |
| `Session.php` | Add SameSite ini in `init()`, add `token()` method for CSRF |
| `View.php` | Remove `$_GET` global, sanitize `HTTP_HOST`, add `csrf_token()`/`csrf_field()` Twig functions |
| `Error.php` | Wrap exception output in `htmlspecialchars()` |
| `Router.php` | Add `middlewareAlias()`, `globalMiddleware()`, integrate middleware into `group()` and fluent routes |
| `BaseRouter.php` | Build Pipeline in `dispatch()` before controller call |

### Auth scaffolding

| File | Change |
|------|--------|
| `AuthHelper.php` | Add `session_regenerate_id(true)` after login |
| `AuthController.php` | Add `RateLimit::clear()` on successful auth |
| Login view | Add `{{ csrf_field() }}` to form |
| Signup view | Add `{{ csrf_field() }}` to form |

### New files

| File | Purpose |
|------|---------|
| `src/Simple/Middleware/Middleware.php` | Interface |
| `src/Simple/Middleware/Pipeline.php` | Pipeline executor |
| `src/Simple/Middleware/Csrf.php` | CSRF middleware |
| `src/Simple/Middleware/Auth.php` | Auth middleware |
| `src/Simple/Middleware/SecurityHeaders.php` | Security headers middleware |
| `src/Simple/Middleware/RateLimit.php` | Rate limiting middleware |

### Simply-docs

| File | Change |
|------|--------|
| `app/Config/global.php` | Default `SHOW_ERRORS` to `false`, add `CSP_POLICY` constant |

---

## Testing strategy

### Phase 0 tests (in existing test files or new SecurityTest.php)

- Session: verify `session_regenerate_id()` called after mocked login
- Error: XSS chars in exception message are escaped in output
- FileUpload: invalid MIME type with valid extension is rejected
- Request: `redirect('http://evil.com')` throws/throws error

### Middleware tests (MiddlewareTest.php)

- Pipeline: middleware can short-circuit or pass through
- Csrf: valid token passes, invalid token throws, GET skips check
- Auth: authenticated user passes, unauthenticated redirects
- SecurityHeaders: headers present in response
- RateLimit: exceeded limit returns 429, clears on reset
- Router integration: middleware applied via group and via fluent method

Tests run via PHPUnit under Docker (`php:8.4-cli` or `php:8.2-cli`).
