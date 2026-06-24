# Config System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. **Use TDD for every change — write failing test first, confirm it fails, implement, confirm it passes, commit.**

**Goal:** Migrate from `define()` constants to Laravel-style array config files + `.env` via `vlucas/phpdotenv`, with `define()` auto-generated for backward compatibility.

**Architecture:** New `Config` class loads `app/Config/*.php` files (each returning an array) into an in-memory repository accessed via dot notation. `env()` helper reads from `$_ENV`. `config()` helper wraps `Config::get()`. `Application` class orchestrates boot sequence. Constants are auto-`define()`'d from config values for BC.

**Tech Stack:** PHP 7.4+/8.0+, PHPUnit 10, `vlucas/phpdotenv` ^5.4

---

### Task 1: Add phpdotenv + Create Config class

**Files:**
- Modify: `composer.json` (add phpdotenv)
- Create: `src/Simple/Config.php`
- Create: `test/ConfigTest.php`

- [ ] **1a: Add phpdotenv dependency**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "composer require vlucas/phpdotenv"
```

Expected: Added to composer.json require section.

- [ ] **1b: Write failing Config test**

Create `test/ConfigTest.php`:

```php
<?php

namespace Simple\Tests;

use PHPUnit\Framework\TestCase;
use Simple\Config;

class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        Config::clear();
    }

    protected function tearDown(): void
    {
        Config::clear();
    }

    public function testSetAndGet(): void
    {
        Config::set('app.name', 'Simply PHP');
        $this->assertEquals('Simply PHP', Config::get('app.name'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertEquals('default', Config::get('nonexistent.key', 'default'));
    }

    public function testHas(): void
    {
        Config::set('foo.bar', 'baz');
        $this->assertTrue(Config::has('foo.bar'));
        $this->assertFalse(Config::has('foo.nonexistent'));
    }

    public function testDotNotationSetsNestedArray(): void
    {
        Config::set('database.server', 'localhost');
        Config::set('database.name', 'testdb');
        $db = Config::get('database');
        $this->assertEquals(['server' => 'localhost', 'name' => 'testdb'], $db);
    }

    public function testLoadFromFiles(): void
    {
        $tmpDir = sys_get_temp_dir() . '/config-test-' . getmypid();
        mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir . '/app.php', '<?php return ["name" => "TestApp"];');
        file_put_contents($tmpDir . '/database.php', '<?php return ["host" => "localhost"];');

        Config::load($tmpDir);
        $this->assertEquals('TestApp', Config::get('app.name'));
        $this->assertEquals('localhost', Config::get('database.host'));

        array_map('unlink', glob($tmpDir . '/*'));
        rmdir($tmpDir);
    }

    public function testLoadDefinesConstants(): void
    {
        $tmpDir = sys_get_temp_dir() . '/config-test-bc-' . getmypid();
        mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir . '/app.php', '<?php return ["name" => "BCApp"];');

        Config::load($tmpDir);
        $this->assertEquals('BCApp', APP_NAME);

        array_map('unlink', glob($tmpDir . '/*'));
        rmdir($tmpDir);
    }
}
```

- [ ] **1c: Run test — verify it fails**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit test/ConfigTest.php --testdox 2>&1"
```

Expected: Tests fail with class not found or method errors.

- [ ] **1d: Create Config class**

`src/Simple/Config.php`:

```php
<?php

namespace Simple;

class Config
{
    protected static array $items = [];
    protected static bool $loaded = false;

    protected static array $bcMap = [
        'app.name' => 'APP_NAME',
        'app.description' => 'APP_DESCRIPTION',
        'app.baseurl' => 'BASEURL',
        'app.key' => 'APP_KEY',
        'database.engine' => 'DBENGINE',
        'database.server' => 'DBSERVER',
        'database.name' => 'DBNAME',
        'database.user' => 'DBUSER',
        'database.pass' => 'DBPASS',
        'database.test_mode' => 'DBTESTMODE',
        'cache.views' => 'CACHE_VIEWS',
        'security.show_errors' => 'SHOW_ERRORS',
        'security.error_handler' => 'ERROR_HANDLER',
        'security.csp_policy' => 'CSP_POLICY',
        'security.rate_limit_max' => 'RATE_LIMIT_MAX_ATTEMPTS',
        'security.rate_limit_decay' => 'RATE_LIMIT_DECAY_SECONDS',
        'security.rate_limit_storage' => 'RATE_LIMIT_STORAGE',
    ];

    public static function load(string $configDir = null): void
    {
        if (static::$loaded) {
            return;
        }

        if ($configDir === null) {
            $configDir = dirname(__DIR__, 4) . '/app/Config';
        }

        foreach (glob($configDir . '/*.php') as $file) {
            $group = basename($file, '.php');
            $values = require $file;
            if (is_array($values)) {
                static::$items[$group] = $values;
            }
        }

        static::defineConstants();

        static::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = static::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function has(string $key): bool
    {
        return static::get($key, '__MISSING__') !== '__MISSING__';
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &static::$items;

        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }

        $target = $value;
    }

    public static function clear(): void
    {
        static::$items = [];
        static::$loaded = false;
    }

    protected static function defineConstants(): void
    {
        foreach (static::$bcMap as $key => $constant) {
            $value = static::get($key);
            if ($value !== null && !defined($constant)) {
                define($constant, $value);
            }
        }
    }
}
```

- [ ] **1e: Run test — verify it passes**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit test/ConfigTest.php --testdox 2>&1"
```

Expected: All 6 tests pass.

- [ ] **1f: Commit**

```bash
git add composer.json composer.lock src/Simple/Config.php test/ConfigTest.php
git commit -m "feat: add Config class with dot-notation access and BC constant defines"
```

---

### Task 2: Add env() and config() helpers

**Files:**
- Modify: `src/Simple/functions.php`
- Modify: `test/ConfigTest.php`

- [ ] **2a: Write failing test for env() helper**

Append to `test/ConfigTest.php`:

```php
public function testEnvReturnsDefaultWhenNotSet(): void
{
    unset($_SERVER['TEST_VAR']);
    $this->assertEquals('default', env('TEST_VAR', 'default'));
}

public function testEnvCastsBooleanStrings(): void
{
    putenv('TEST_TRUE=true');
    putenv('TEST_FALSE=false');
    $this->assertTrue(env('TEST_TRUE'));
    $this->assertFalse(env('TEST_FALSE'));
}

public function testEnvCastsNullString(): void
{
    putenv('TEST_NULL=null');
    $this->assertNull(env('TEST_NULL'));
}
```

- [ ] **2b: Run test — verify env tests fail**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit test/ConfigTest.php --testdox --filter 'env|Env' 2>&1"
```

- [ ] **2c: Add env() and config() helpers to functions.php**

Add before the closing `?>` (or at the end of file if no closing tag):

```php
if (!function_exists('env'))
{
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (!function_exists('config'))
{
    function config(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return \Simple\Config::get('', $default);
        }
        return \Simple\Config::get($key, $default);
    }
}
```

Also add `use Simple\Config;` to the top of the file.

- [ ] **2d: Run test — verify env tests pass**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit test/ConfigTest.php --testdox --filter 'env|Env' 2>&1"
```

- [ ] **2e: Run all Config tests**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit test/ConfigTest.php --testdox 2>&1"
```

- [ ] **2f: Commit**

```bash
git add src/Simple/functions.php test/ConfigTest.php
git commit -m "feat: add env() and config() helper functions"
```

---

### Task 3: Create Application class

**Files:**
- Create: `src/Simple/Application.php`
- Create: `test/ApplicationTest.php`

- [ ] **3a: Write failing Application test**

Create `test/ApplicationTest.php`:

```php
<?php

namespace Simple\Tests;

use PHPUnit\Framework\TestCase;
use Simple\Application;

class ApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    public function testBootLoadsConfig(): void
    {
        $tmpDir = sys_get_temp_dir() . '/app-test-' . getmypid();
        mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir . '/app.php', '<?php return ["name" => "AppBootTest"];');

        $_ENV['APP_NAME'] = 'Override';
        $app = new Application();
        $app->boot($tmpDir);

        $this->assertEquals('Override', \Simple\Config::get('app.name'));

        array_map('unlink', glob($tmpDir . '/*'));
        rmdir($tmpDir);
    }

    public function testBootHandlesMissingConfigDir(): void
    {
        $app = new Application();
        $app->boot('/nonexistent/path');
        $this->assertTrue(true);
    }
}
```

- [ ] **3b: Run test — verify it fails**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit test/ApplicationTest.php --testdox 2>&1"
```

- [ ] **3c: Create Application class**

`src/Simple/Application.php`:

```php
<?php

namespace Simple;

use Dotenv\Dotenv;

class Application
{
    public function boot(string $configDir = null): void
    {
        $this->loadEnvironment();
        Config::load($configDir);
        $this->initSession();
        $this->setErrorHandler();
    }

    protected function loadEnvironment(): void
    {
        $basePath = dirname(__DIR__, 4);
        $envPath = $basePath;

        if (file_exists($envPath . '/.env')) {
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->safeLoad();
        }
    }

    protected function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                ini_set('session.cookie_secure', 1);
            }
            session_start();
        }
    }

    protected function setErrorHandler(): void
    {
        error_reporting(E_ALL);
        $handler = defined('ERROR_HANDLER') ? ERROR_HANDLER : 'simply';

        if ($handler === 'simply') {
            set_error_handler('Simple\Error::errorHandler');
            set_exception_handler('Simple\Error::exceptionHandler');
        } elseif ($handler === 'whoops') {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            $whoops->register();
        } else {
            set_error_handler('Simple\Error::errorHandler');
            set_exception_handler('Simple\Error::exceptionHandler');
        }
    }
}
```

- [ ] **3d: Run test — verify it passes**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit test/ApplicationTest.php --testdox 2>&1"
```

- [ ] **3e: Run all existing tests to check no regressions**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit --testdox test/ 2>&1"
```

- [ ] **3f: Commit**

```bash
git add src/Simple/Application.php test/ApplicationTest.php
git commit -m "feat: add Application class with boot() — env, config, session, error handler"
```

---

### Task 4: Update simple-php template

**Files:**
- Modify: `../simple-php/composer.json`
- Delete: `../simple-php/app/Config/global.php`
- Create: `../simple-php/app/Config/app.php`
- Create: `../simple-php/app/Config/database.php`
- Create: `../simple-php/app/Config/cache.php`
- Create: `../simple-php/app/Config/security.php`
- Create: `../simple-php/.env`
- Create: `../simple-php/.env.example`
- Modify: `../simple-php/public/index.php`
- Modify: `../simple-php/.gitignore`

All paths are relative to `/Users/rj/web/simply-php/simple-php`.

- [ ] **4a: Update simple-php composer.json to require vlucas/phpdotenv**

Read `composer.json` and add `"vlucas/phpdotenv": "^5.4"` to `require`:

```json
"require": {
    "simplyphp/framework": "v2.0.*",
    "php": "^7.4|^8.0",
    "vlucas/phpdotenv": "^5.4"
}
```

- [ ] **4b: Create config files**

`app/Config/app.php`:
```php
<?php

return [
    'name'        => env('APP_NAME', 'Simply PHP'),
    'description' => env('APP_DESCRIPTION', 'The "Simply-PHP" Framework'),
    'baseurl'     => env('BASEURL', ''),
    'key'         => env('APP_KEY', ''),
];
```

`app/Config/database.php`:
```php
<?php

return [
    'engine'    => env('DBENGINE', 'mysql'),
    'server'    => env('DBSERVER', 'localhost'),
    'name'      => env('DBNAME', 'simply'),
    'user'      => env('DBUSER', 'root'),
    'pass'      => env('DBPASS', ''),
    'test_mode' => env('DBTESTMODE', false),
];
```

`app/Config/cache.php`:
```php
<?php

return [
    'views' => env('CACHE_VIEWS', false),
];
```

`app/Config/security.php`:
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

- [ ] **4c: Delete old global.php**

```bash
rm app/Config/global.php
```

- [ ] **4d: Create .env and .env.example**

`.env` (empty values for dev, gitignored):
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

`.env.example` (same content, committed):
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

- [ ] **4e: Update front controller**

Replace `public/index.php`:

```php
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$app = new \Simple\Application();
$app->boot();

$url = \Simple\url_init();

require '../app/Routes.php';
\Simple\Routing\Router::dispatch($url);
```

- [ ] **4f: Add .env to gitignore**

Read `.gitignore` and add a line for `.env` if not already present.

- [ ] **4g: Run composer install to update lock file**

```bash
cd /Users/rj/web/simply-php/simple-php && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "composer install 2>&1"
```

- [ ] **4h: Commit**

```bash
git add -A && git commit -m "feat: migrate to array config + .env support"
```

Work from: /Users/rj/web/simply-php/simple-php

---

### Task 5: Update simply-docs

**Files:**
- Modify: `../simply-docs/composer.json`
- Delete: `../simply-docs/app/Config/global.php`
- Create: `../simply-docs/app/Config/app.php`
- Create: `../simply-docs/app/Config/database.php`
- Create: `../simply-docs/app/Config/cache.php`
- Create: `../simply-docs/app/Config/security.php`
- Create: `../simply-docs/.env`
- Create: `../simply-docs/.env.example`
- Modify: `../simply-docs/public/index.php`
- Modify: `../simply-docs/.gitignore`

All paths relative to `/Users/rj/web/simply-php/simply-docs`.

Same changes as Task 4 but for the simply-docs project. Values should reflect simply-docs defaults:

`app/Config/app.php`:
```php
<?php

return [
    'name'        => env('APP_NAME', 'Simply PHP Documentation'),
    'description' => env('APP_DESCRIPTION', 'The "Simply-PHP" Framework'),
    'baseurl'     => env('BASEURL', ''),
    'key'         => env('APP_KEY', 'replace-this-with-a-secure-random-key'),
];
```

`app/Config/database.php`:
```php
<?php

return [
    'engine'    => env('DBENGINE', 'mysqli'),
    'server'    => env('DBSERVER', 'localhost'),
    'name'      => env('DBNAME', 'simply'),
    'user'      => env('DBUSER', 'root'),
    'pass'      => env('DBPASS', ''),
    'test_mode' => env('DBTESTMODE', false),
];
```

`app/Config/cache.php`:
```php
<?php

return [
    'views' => env('CACHE_VIEWS', false),
];
```

`app/Config/security.php`:
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

`public/index.php`: Same as Task 4 — `$app = new \Simple\Application(); $app->boot();`

- [ ] **5a: Read current global.php values to preserve settings**

```bash
cat /Users/rj/web/simply-php/simply-docs/app/Config/global.php
```

- [ ] **5b: Create config files, .env, .env.example, delete global.php, update index.php**

Make all changes listed above.

- [ ] **5c: Verify page loads**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/ && curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/documentation/v1/lib/security
```

- [ ] **5d: Commit**

```bash
cd /Users/rj/web/simply-php/simply-docs && git add -A && git commit -m "feat: migrate to array config + .env support"
```

---

### Task 6: Update test files to use Config::set()

When framework source files switch to `Config::get()`, tests need Config populated with matching values so the DB connection, error handling, etc. work during tests.

**Files:**
- Modify: `test/app/Model.php`
- Modify: `test/SecurityTest.php`
- Modify: `test/RouterTest.php`
- Modify: `test/MiddlewareTest.php`

- [ ] **6a: Add Config::set() calls to test files**

In each test file, after the `define()` guard blocks, add matching `Config::set()` calls:

In `test/app/Model.php`, after the define block:
```php
\Simple\Config::set('database.engine', DBENGINE);
\Simple\Config::set('database.server', DBSERVER);
\Simple\Config::set('database.name', DBNAME);
\Simple\Config::set('database.user', DBUSER);
\Simple\Config::set('database.pass', DBPASS);
\Simple\Config::set('app.name', APP_NAME);
\Simple\Config::set('app.description', APP_DESCRIPTION);
\Simple\Config::set('app.baseurl', BASEURL);
\Simple\Config::set('app.key', APP_KEY);
\Simple\Config::set('security.show_errors', SHOW_ERRORS);
\Simple\Config::set('security.error_handler', ERROR_HANDLER);
```

In `test/SecurityTest.php`, after the define block:
```php
\Simple\Config::set('database.engine', DBENGINE);
\Simple\Config::set('database.server', DBSERVER);
\Simple\Config::set('database.name', DBNAME);
\Simple\Config::set('database.user', DBUSER);
\Simple\Config::set('database.pass', DBPASS);
\Simple\Config::set('security.show_errors', defined('SHOW_ERRORS') ? SHOW_ERRORS : true);
```

In `test/RouterTest.php`, after the define block:
```php
\Simple\Config::set('database.engine', DBENGINE);
\Simple\Config::set('database.server', DBSERVER);
\Simple\Config::set('database.name', DBNAME);
\Simple\Config::set('database.user', DBUSER);
\Simple\Config::set('database.pass', DBPASS);
```

In `test/MiddlewareTest.php`, after the define block:
```php
\Simple\Config::set('database.engine', DBENGINE);
\Simple\Config::set('database.server', DBSERVER);
\Simple\Config::set('database.name', DBNAME);
\Simple\Config::set('database.user', DBUSER);
\Simple\Config::set('database.pass', DBPASS);
\Simple\Config::set('security.rate_limit_max', RATE_LIMIT_MAX_ATTEMPTS);
\Simple\Config::set('security.rate_limit_decay', RATE_LIMIT_DECAY_SECONDS);
\Simple\Config::set('security.rate_limit_storage', RATE_LIMIT_STORAGE);
```

- [ ] **6b: Run tests to verify no regression**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit --testdox test/ 2>&1"
```

Expected: All tests pass with Config::get() available.

- [ ] **6c: Commit**

```bash
git add test/app/Model.php test/SecurityTest.php test/RouterTest.php test/MiddlewareTest.php
git commit -m "test: add Config::set() calls to test files for Config system compatibility"
```

---

### Task 7: Update framework source files to use Config

**Files:**
- Modify: `src/Simple/Error.php`
- Modify: `src/Simple/View.php`
- Modify: `src/Simple/Database/Connection.php`
- Modify: `src/Simple/Engine/Console.php`
- Modify: `src/Simple/Middleware/RateLimit.php`
- Modify: `src/Simple/Middleware/SecurityHeaders.php`
- Modify: `src/Simple/Security/Encryption.php`
- Modify: `src/Simple/Request.php`

Each file currently reads global constants directly. Replace with `Config::get()` calls.

- [ ] **7a: Update Error.php**

Read `src/Simple/Error.php`. Inside `exceptionHandler()`, replace:
- `if (SHOW_ERRORS == true)` → `if (Config::get('security.show_errors', true))`
- `APP_NAME` in lines ~122,126 → `Config::get('app.name', 'Simply PHP')`

Add `use Simple\Config;` to top of file.

- [ ] **7b: Update View.php**

Read `src/Simple/View.php`. Inside `render()`, replace:
- `if (CACHE_VIEWS == true)` → `if (Config::get('cache.views', false))`
- `'debug' => SHOW_ERRORS` → `'debug' => Config::get('security.show_errors', true)`

- [ ] **7c: Update Connection.php**

Read `src/Simple/Database/Connection.php`. In the constructor/initialization, replace each DB constant:
```php
// Before:
define('DBENGINE', DBENGINE);
$this->engine = DBENGINE;
// Or in constructor:
$DBENGINE = DBENGINE;

// After:
$DBENGINE = Config::get('database.engine', 'mysql');
```

Read the file to see exact usage pattern and replace accordingly.

- [ ] **7d: Update Console.php**

Read `src/Simple/Engine/Console.php`. Replace all DB constant reads with `Config::get('database.*')`. Update the `writeConfigFile()` method to write array config instead of `define()` calls:

```php
// In writeConfigFile(), replace the content that writes define() calls
// with code that writes PHP array return syntax:
$config = "<?php\n\nreturn [\n";
$config .= "    'name' => '{$appName}',\n";
$config .= "    'description' => '{$appDesc}',\n";
$config .= "    'baseurl' => '{$baseUrl}',\n";
$config .= "    'key' => '{$key}',\n";
$config .= "];\n";
```

Also update the key:generate section that currently writes `define('APP_KEY', ...)` to instead write array format.

- [ ] **7e: Update RateLimit.php**

Read `src/Simple/Middleware/RateLimit.php`. In constructor:
```php
// Before:
$this->maxAttempts = defined('RATE_LIMIT_MAX_ATTEMPTS') ? RATE_LIMIT_MAX_ATTEMPTS : 5;

// After:
$this->maxAttempts = Config::get('security.rate_limit_max', 5);
```

Same for `decaySeconds` and `storageDir` — replace all three `defined()` guard patterns. Also update the `clear()` static method similarly.

- [ ] **7f: Update SecurityHeaders.php**

Read `src/Simple/Middleware/SecurityHeaders.php`:
```php
// Before:
$csp = defined('CSP_POLICY') ? CSP_POLICY : "default-src 'self'";

// After:
$csp = Config::get('security.csp_policy', "default-src 'self'");
```

- [ ] **7g: Update Encryption.php**

Read `src/Simple/Security/Encryption.php` and replace `APP_KEY` with `Config::get('app.key')`.

- [ ] **7h: Update Request.php**

Read `src/Simple/Request.php` and replace `BASEURL` with `Config::get('app.baseurl', '')`.

- [ ] **7i: Run full test suite**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit --testdox test/ 2>&1"
```

Expected: All tests pass.

- [ ] **7j: Commit**

```bash
git add src/Simple/Error.php src/Simple/View.php src/Simple/Database/Connection.php src/Simple/Engine/Console.php src/Simple/Middleware/RateLimit.php src/Simple/Middleware/SecurityHeaders.php src/Simple/Security/Encryption.php src/Simple/Request.php
git commit -m "refactor: migrate framework source files from define() to Config::get()"
```

---

### Task 8: Run final verification

- [ ] **8a: Run full test suite**

```bash
cd /Users/rj/web/simply-php/framework && docker run --rm -v .:/app -w /app php:8.4-cli bash -c "php vendor/bin/phpunit --testdox test/ 2>&1"
```

- [ ] **8b: Verify simply-docs loads**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/documentation/v1/lib/security
```

- [ ] **8c: Verify constants still accessible for BC**

```bash
docker run --rm -v .:/app -w /app php:8.4-cli bash -c 'php -r "
require_once \"vendor/autoload.php\";
echo SHOW_ERRORS ? \"SHOW_ERRORS=true\n\" : \"SHOW_ERRORS=false\n\";
echo defined(\"DBENGINE\") ? \"DBENGINE is defined\n\" : \"DBENGINE not defined\n\";
"'
```

Expected: `SHOW_ERRORS=true`, `DBENGINE is defined` (using test config or test defaults).

- [ ] **8d: Final commit if anything changed**

```bash
git add -A && git commit -m "chore: finalize config system migration"
```
