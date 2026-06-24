# Config System Design

> **Date:** 2026-06-24
> **Status:** Approved Design
> **Migrates from:** `define()` constants in `app/Config/global.php`
> **Migrates to:** Laravel-style array config files + `.env` via `vlucas/phpdotenv`

## Motivation

The framework currently uses PHP `define()` constants for all configuration (`SHOW_ERRORS`, `DBENGINE`, `APP_NAME`, etc.). This approach has several limitations:

- No support for environment-specific values — same config for dev/prod
- Hard-coded sensitive credentials (DB passwords) in version control
- No dot-notation access, no config inheritance or fallback
- Constants are global and can conflict with other libraries
- No `.env` file support

## Architecture

### Data flow

```
.env file
    │
    ▼ (vlucas/phpdotenv loads into $_ENV / getenv())
    │
env() helper reads from here
    │
    ▼
Config files (app/Config/*.php return arrays)
    │  each value can use env('KEY', 'default')
    │
    ▼
Config class loads all files into one in-memory array
    │
    ├──► Config::get('database.host') / config('database.host')
    │
    └──► define() auto-generated for backward compatibility
         (so SHOW_ERRORS, DBENGINE etc. still work as globals)
```

### Components

#### 1. `src/Simple/Config.php` — Config Repository

```php
namespace Simple;

class Config
{
    protected static array $items = [];
    protected static bool $loaded = false;

    public static function load(): void;
    public static function get(string $key, mixed $default = null): mixed;
    public static function has(string $key): bool;
    public static function set(string $key, mixed $value): void;
}
```

- `$key` uses dot notation: `database.server`, `security.show_errors`
- `load()` scans `../app/Config/*.php`, requires each, merges arrays, and calls `define()` for BC
- Files in `app/Config/` return PHP arrays:

```php
// app/Config/database.php
return [
    'engine' => env('DBENGINE', 'mysql'),
    'server' => env('DBSERVER', 'localhost'),
    'name'   => env('DBNAME'),
    'user'   => env('DBUSER'),
    'pass'   => env('DBPASS'),
];
```

#### 2. `src/Simple/Application.php` — Application Bootstrap

```php
namespace Simple;

class Application
{
    public function boot(): void;
}
```

`boot()` orchestrates in order:
1. Load `.env` via `Dotenv\Dotenv::createImmutable()`
2. Call `Config::load()` to load config files and define constants
3. Start session
4. Set error/exception handler based on config

#### 3. Global helper functions

In `src/Simple/functions.php` (already autoloaded):

```php
if (!function_exists('config')) {
    function config(string $key = null, $default = null) {
        if ($key === null) return \Simple\Config::$items;
        return \Simple\Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false) return $default;
        // Handle special values: true, false, null, empty string
        return match (strtolower($value)) {
            'true', '(true)'  => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            ''                 => '',
            default            => $value,
        };
    }
}
```

These are **global** functions (not namespaced) for convenience, matching Laravel's pattern.

### Config File Structure

In both `simple-php/` (create-project template) and `simply-docs/`:

```
app/
  Config/
    app.php           // APP_NAME, APP_DESCRIPTION, BASEURL, APP_KEY
    database.php      // DBENGINE, DBSERVER, DBNAME, DBUSER, DBPASS
    cache.php         // CACHE_VIEWS
    security.php      // SHOW_ERRORS, CSP_POLICY, RATE_LIMIT_*, ERROR_HANDLER
  .env                // actual values (gitignored)
  .env.example        // template (committed)
```

#### `app/Config/app.php`

```php
<?php

return [
    'name'        => env('APP_NAME', 'Simply PHP'),
    'description' => env('APP_DESCRIPTION', 'The "Simply-PHP" Framework'),
    'baseurl'     => env('BASEURL', ''),
    'key'         => env('APP_KEY', ''),
];
```

#### `app/Config/database.php`

```php
<?php

return [
    'engine' => env('DBENGINE', 'mysql'),
    'server' => env('DBSERVER', 'localhost'),
    'name'   => env('DBNAME', 'simply'),
    'user'   => env('DBUSER', 'root'),
    'pass'   => env('DBPASS', ''),
    'test_mode' => env('DBTESTMODE', false),
];
```

#### `app/Config/cache.php`

```php
<?php

return [
    'views' => env('CACHE_VIEWS', false),
];
```

#### `app/Config/security.php`

```php
<?php

return [
    'show_errors'          => env('SHOW_ERRORS', false),
    'error_handler'        => env('ERROR_HANDLER', 'simply'),
    'csp_policy'           => env('CSP_POLICY', "default-src 'self'"),
    'rate_limit_max'       => env('RATE_LIMIT_MAX_ATTEMPTS', 5),
    'rate_limit_decay'     => env('RATE_LIMIT_DECAY_SECONDS', 60),
    'rate_limit_storage'   => env('RATE_LIMIT_STORAGE', '../app/storage/framework/rate-limit'),
];
```

#### `.env.example`

```env
APP_NAME="Simply PHP"
APP_DESCRIPTION="The Simply-PHP Framework"
APP_KEY=
BASEURL=

SHOW_ERRORS=true
ERROR_HANDLER=simply

CACHE_VIEWS=false

DBENGINE=mysql
DBSERVER=localhost
DBNAME=simply
DBUSER=root
DBPASS=

CSP_POLICY="default-src 'self'"
RATE_LIMIT_MAX_ATTEMPTS=5
RATE_LIMIT_DECAY_SECONDS=60
```

### define() Auto-generation

When `Config::load()` merges config arrays, it also `define()`s the corresponding constants for backward compatibility:

| Config key | Constant name |
|---|---|
| `app.name` | `APP_NAME` |
| `app.description` | `APP_DESCRIPTION` |
| `app.baseurl` | `BASEURL` |
| `app.key` | `APP_KEY` |
| `database.engine` | `DBENGINE` |
| `database.server` | `DBSERVER` |
| ... | ... |

The mapping is hard-coded in `Config::load()`. Existing code reading `SHOW_ERRORS`, `DBENGINE`, etc. continues to work unchanged.

### Front Controller Update

In both `simple-php/public/index.php` and `simply-docs/public/index.php`:

```php
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$app = new \Simple\Application();
$app->boot();

$url = \Simple\url_init();

require '../app/Routes.php';
\Simple\Routing\Router::dispatch($url);
```

The `Application::boot()` replaces the inline config loading, session init, and error handler setup.

## Migration Plan

### Files to update

#### Framework (`framework/`)

| File | Action |
|------|--------|
| `composer.json` | Add `vlucas/phpdotenv` to `require` |
| `src/Simple/Config.php` | **Create** — Config repository |
| `src/Simple/Application.php` | **Create** — Application bootstrap |
| `src/Simple/functions.php` | Add `config()` and `env()` global helpers |
| `src/Simple/Error.php` | Update to use `Config::get('security.show_errors')` / `config('app.name')` |
| `src/Simple/View.php` | Update to use `Config::get()` |
| `src/Simple/Database/Connection.php` | Update to use `Config::get()` |
| `src/Simple/Engine/Console.php` | Update to use `Config::get()` |
| `src/Simple/Middleware/RateLimit.php` | Update to use `Config::get()` |
| `src/Simple/Middleware/SecurityHeaders.php` | Update to use `Config::get()` |
| `src/Simple/Security/Encryption.php` | Update to use `Config::get()` |
| `src/Simple/Request.php` | Update to use `Config::get()` |

#### Template (`simple-php/`)

| File | Action |
|------|--------|
| `app/Config/global.php` | **Remove** — replaced by individual files |
| `app/Config/app.php` | **Create** |
| `app/Config/database.php` | **Create** |
| `app/Config/cache.php` | **Create** |
| `app/Config/security.php` | **Create** |
| `.env` | **Create** (gitignored) |
| `.env.example` | **Create** (committed) |
| `public/index.php` | Update to use Application::boot() |
| `.gitignore` | Add `.env` |

#### Docs site (`simply-docs/`)

Same changes as `simple-php/` above (config files, `.env`, `index.php`).

#### Test files (`framework/test/`)

| File | Action |
|------|--------|
| `test/app/Model.php` | Replace `define()` guards with Config bootstrap |
| `test/SecurityTest.php` | Replace `define()` guards with Config bootstrap |
| `test/RouterTest.php` | Replace `define()` guards with Config bootstrap |
| `test/MiddlewareTest.php` | Replace `define()` guards with Config bootstrap |

### Order of implementation

1. Add `vlucas/phpdotenv` to framework `composer.json`
2. Create `Config.php` and `Application.php`
3. Add `config()` and `env()` helpers to `functions.php`
4. Update `simple-php/` config files and front controller
5. Update `simply-docs/` config files and front controller
6. Update framework source files to use the new system (8 files)
7. Update test files
8. Run full test suite

## Testing Strategy

- Unit test `Config` class: load, get, has, set, dot notation
- Unit test `env()` helper: true/false/null casting, defaults
- Unit test `Application::boot()`: verify config loads, session starts, error handler set
- Integration: existing tests pass (defines still work via BC)
- Verify `.env` is loaded correctly in a Docker environment

## Security Considerations

- `.env` file must be in `.gitignore` — never commit secrets
- Application class must handle missing `.env` gracefully (use defaults)
- For BC, all values including credentials get `define()`'d. When reading secrets in application code, prefer `Config::get()` directly instead of the global constant, as constants are accessible in stack traces and `get_defined_constants()`
