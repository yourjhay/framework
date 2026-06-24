<?php declare(strict_types=1);

namespace Simple;

use Monolog\Logger as MonologLogger;

class Log
{
    private static ?MonologLogger $logger = null;
    private static array $globalContext = [];

    public static function setLogger(?MonologLogger $logger): void
    {
        static::$logger = $logger;
    }

    public static function withContext(array $context): void
    {
        static::$globalContext = array_merge(static::$globalContext, $context);
    }

    public static function __callStatic(string $method, array $args): void
    {
        if (static::$logger === null) {
            return;
        }

        if ($method === 'log') {
            $level = $args[0] ?? 'debug';
            $message = $args[1] ?? '';
            $context = array_merge(static::$globalContext, $args[2] ?? []);
            static::$logger->log($level, $message, $context);
        } else {
            $message = $args[0] ?? '';
            $context = array_merge(static::$globalContext, $args[1] ?? []);
            static::$logger->$method($message, $context);
        }
    }
}
