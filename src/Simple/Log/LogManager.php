<?php declare(strict_types=1);

namespace Simple\Log;

use Simple\Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Formatter\LineFormatter;

class LogManager
{
    public static function make(array $config): Logger
    {
        $logger = new Logger($config['name'] ?? 'simply');
        $level = $config['level'] ?? 'debug';
        $handler = match($config['handler'] ?? 'daily') {
            'daily'    => new RotatingFileHandler(
                self::resolvePath($config['path'] ?? './storage/logs/app.log'),
                $config['days'] ?? 14,
                $level
            ),
            'single'   => new StreamHandler(
                self::resolvePath($config['path'] ?? './storage/logs/app.log'),
                $level
            ),
            'stderr'   => new StreamHandler('php://stderr', $level),
            'syslog'   => new SyslogHandler(
                $config['name'] ?? 'simply',
                LOG_USER,
                $level
            ),
            'errorlog' => new ErrorLogHandler(
                ErrorLogHandler::OPERATING_SYSTEM,
                $level
            ),
            default    => new StreamHandler(
                self::resolvePath($config['path'] ?? './storage/logs/app.log'),
                $level
            ),
        };
        $logger->pushHandler(self::formatHandler($handler));
        return $logger;
    }

    private static function formatHandler($handler)
    {
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message%\n",
            null,
            true
        );
        $handler->setFormatter($formatter);
        return $handler;
    }

    public static function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || str_starts_with($path, 'php://')) {
            return $path;
        }
        $root = Config::get('app.project_root', getcwd());
        return $root . '/' . ltrim($path, './');
    }
}
