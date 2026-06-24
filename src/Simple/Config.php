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

    public static function load(?string $configDir = null): void
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
