<?php

namespace Simple;

class Config
{
    protected static array $items = [];
    protected static bool $loaded = false;

    public static function load(?string $configDir = null): void
    {
        if (static::$loaded) {
            return;
        }

        if ($configDir === null) {
            $configDir = dirname(__DIR__, 5) . '/app/Config';
        }

        foreach (glob($configDir . '/*.php') as $file) {
            $group = basename($file, '.php');
            $values = require $file;
            if (is_array($values)) {
                static::$items[$group] = $values;
            }
        }

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
        $segments = explode('.', $key);
        $value = static::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &static::$items;

        foreach ($segments as $segment) {
            if (!array_key_exists($segment, $target) || !is_array($target[$segment])) {
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

}
