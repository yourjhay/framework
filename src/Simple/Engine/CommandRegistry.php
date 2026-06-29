<?php

namespace Simple\Engine;

class CommandRegistry
{
    private static array $commands = [
        'make:controller' => Commands\MakeControllerCommand::class,
        'make:model'      => Commands\MakeModelCommand::class,
        'make:observer'   => Commands\MakeObserverCommand::class,
        'make:request'    => Commands\MakeRequestCommand::class,
        'make:auth'       => Commands\MakeAuthCommand::class,
        'make:migration'   => Commands\MakeMigrationCommand::class,
        'migrate'          => Commands\MigrateCommand::class,
        'migrate:fresh'    => Commands\MigrateFreshCommand::class,
        'migrate:status'   => Commands\MigrateStatusCommand::class,
        'session:destroy' => Commands\SessionDestroyCommand::class,
        'cache:clear'     => Commands\CacheClearCommand::class,
        'serve'           => Commands\ServeCommand::class,
        'key:generate'    => Commands\KeyGenerateCommand::class,
        'route:list'      => Commands\RouteListCommand::class,
        'help'            => Commands\HelpCommand::class,
        '-help'           => Commands\HelpCommand::class,
    ];

    public static function get(string $name): ?string
    {
        return self::$commands[$name] ?? null;
    }

    public static function all(): array
    {
        return self::$commands;
    }
}
