# Console.php Refactoring Design

## Goal

Refactor `src/Simple/Engine/Console.php` from a 757-line god class into separate domain command classes with stub extraction, keeping the exact same CLI interface.

## Current State

- 757 lines, 16 methods, 14 distinct responsibilities
- Inline PHP/HTML stub templates in 4 methods
- Switch-case dispatch in `consoleRun()`
- Hardcoded paths, inconsistent formatting, security concerns
- Single file in `src/Simple/Engine/Console.php`

## Target Architecture

### Directory Structure

```
src/Simple/Engine/
├── Console.php                    # ~40 lines: thin orchestrator
├── ConsoleOutput.php              # unchanged
├── CommandRegistry.php            # static mapping: command name → class
├── contracts/
│   └── CommandInterface.php       # interface: handle(array $args): ?array
├── Commands/
│   ├── MakeControllerCommand.php
│   ├── MakeModelCommand.php
│   ├── MakeObserverCommand.php
│   ├── MakeRequestCommand.php
│   ├── MakeAuthCommand.php
│   ├── MigrateCommand.php
│   ├── SeedCommand.php
│   ├── SessionDestroyCommand.php
│   ├── CacheClearCommand.php
│   ├── ServeCommand.php
│   ├── KeyGenerateCommand.php
│   ├── RouteListCommand.php
│   └── HelpCommand.php
└── Stubs/
    ├── controller.stub
    ├── controller-resource.stub
    ├── controller-resource-model.stub
    ├── model.stub
    ├── observer.stub
    └── request.stub
```

### CommandInterface

```php
namespace Simple\Engine\Contracts;

interface CommandInterface
{
    /**
     * @param array $args Arguments from argv (excluding command name)
     * @return array|null ['type' => 'success'|'error', 'message' => '...']
     *         null if command handled its own output
     */
    public function handle(array $args): ?array;
}
```

### CommandRegistry

```php
namespace Simple\Engine;

class CommandRegistry
{
    private static array $commands = [
        'make:controller' => Commands\MakeControllerCommand::class,
        'make:model'      => Commands\MakeModelCommand::class,
        'make:observer'   => Commands\MakeObserverCommand::class,
        'make:request'    => Commands\MakeRequestCommand::class,
        'make:auth'       => Commands\MakeAuthCommand::class,
        'migrate'         => Commands\MigrateCommand::class,
        'user:seed'       => Commands\SeedCommand::class,
        'session:destroy' => Commands\SessionDestroyCommand::class,
        'cache:clear'     => Commands\CacheClearCommand::class,
        'serve'           => Commands\ServeCommand::class,
        'key:generate'    => Commands\KeyGenerateCommand::class,
        'route:list'      => Commands\RouteListCommand::class,
        'help'            => Commands\HelpCommand::class,
        '-help'           => Commands\HelpCommand::class,
    ];

    public static function get(string $name): ?string;
    public static function all(): array;
}
```

### Refactored Console.php

~40 lines. No switch-case. No inline logic.

```php
public function consoleRun()
{
    $name = $this->argv[1] ?? null;
    if (!$name) {
        $this->status = 'error: No command provided.';
        return;
    }
    $class = CommandRegistry::get($name);
    if (!$class) {
        $this->status = 'error: ===== Command not found. =====';
        return;
    }
    $result = (new $class)->handle(array_slice($this->argv, 2));
    if ($result) {
        $this->status = $result['type'] . ': ' . $result['message'] . PHP_EOL;
    }
}
```

- `print_status()` and `__construct()` remain unchanged
- `ConsoleOutput` alias `co` import preserved for backward compatibility

### Stub Extraction

Inline templates from `createController()`, `createModel()`, `createObserver()`, `createRequest()` move to `.stub` files in `src/Simple/Engine/Stubs/`.

Placeholder format: `{{placeholderName}}`

Stubs directory:
- `controller.stub` — basic controller
- `controller-resource.stub` — CRUD controller with 7 methods
- `controller-resource-model.stub` — CRUD controller + model creation
- `model.stub` — Eloquent model
- `observer.stub` — observer class
- `request.stub` — FormRequest class

## Constraints

- All existing CLI commands work identically
- Flag options unchanged (`-r`, `-rm`, `-m`, `-c`, etc.)
- `print_status()` output coloring unchanged
- `ConsoleOutput` class untouched
- Existing auth scaffolding stub pattern in vendor/ remains unchanged

## Out of Scope

- DB migration security fixes (shell injection, password exposure)
- Adding strict types project-wide
- Formatting every other file in the framework
