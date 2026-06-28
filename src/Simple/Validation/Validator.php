<?php

namespace Simple\Validation;

class Validator extends \Simple\Validation\GUMP
{
    protected static bool $booted = false;

    public static function boot(): void
    {
        if (static::$booted) {
            return;
        }

        // Suppress deprecation warnings from GUMP's file load (PHP 8.4 compatibility)
        $old_error_level = error_reporting(E_ALL & ~E_DEPRECATED);

        static::add_validator('unique', function ($field, array $input, array $params, $value) {
            $table = $params[0] ?? null;
            $ignore = $params[1] ?? null;
            $ignoreCol = $params[2] ?? 'id';

            $query = \Simple\Database\DB::table($table)->where($field, $value);

            if ($ignore !== null) {
                $query = $query->where($ignoreCol, '!=', $ignore);
            }

            return!$query->exists();
        }, '{field} is already taken.');

        error_reporting($old_error_level);
        static::$booted = true;
    }

    public function __construct(string $lang = 'en')
    {
        static::boot();
        parent::__construct($lang);
    }

    public static function get_instance(): static
    {
        static::boot();
        return parent::get_instance();
    }
}
