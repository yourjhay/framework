# Console.php Refactoring Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split the 757-line `Console.php` god class into separate domain command classes with a registry pattern, extracting inline stub templates to `.stub` files.

**Architecture:** A `CommandInterface` defines `handle(array $args): ?array`. A static `CommandRegistry` maps command names to classes. `Console.php` becomes a thin ~40-line dispatcher. Each command class lives in `src/Simple/Engine/Commands/`. Stubs live in `src/Simple/Engine/Stubs/`.

**Tech Stack:** PHP 8.0+, PHPUnit 10, PSR-4 autoloading

---

### Task 1: Directory Structure + CommandInterface + CommandRegistry + Tests

**Files:**
- Create: `src/Simple/Engine/contracts/CommandInterface.php`
- Create: `src/Simple/Engine/CommandRegistry.php`
- Create: `src/Simple/Engine/Commands/.gitkeep`
- Create: `src/Simple/Engine/Stubs/.gitkeep`
- Create: `test/Engine/CommandRegistryTest.php`

- [ ] **Step 1: Create directory structure**

```bash
mkdir -p src/Simple/Engine/contracts
mkdir -p src/Simple/Engine/Commands
mkdir -p src/Simple/Engine/Stubs
touch src/Simple/Engine/Commands/.gitkeep
touch src/Simple/Engine/Stubs/.gitkeep
```

- [ ] **Step 2: Create CommandInterface**

```php
<?php

namespace Simple\Engine\Contracts;

interface CommandInterface
{
    /**
     * @param array $args Arguments from argv (excluding command name)
     * @return array|null ['type' => 'success'|'error', 'message' => '...']
     *         null if command handled its own output (echoed directly)
     */
    public function handle(array $args): ?array;
}
```

- [ ] **Step 3: Create CommandRegistry**

```php
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

    public static function get(string $name): ?string
    {
        return self::$commands[$name] ?? null;
    }

    public static function all(): array
    {
        return self::$commands;
    }
}
```

- [ ] **Step 4: Write the failing test for CommandRegistry**

```php
<?php

namespace Simple\Tests\Engine;

use PHPUnit\Framework\TestCase;
use Simple\Engine\CommandRegistry;

class CommandRegistryTest extends TestCase
{
    public function testGetReturnsClassForRegisteredCommand(): void
    {
        $class = CommandRegistry::get('help');
        $this->assertNotNull($class);
    }

    public function testGetReturnsNullForUnknownCommand(): void
    {
        $this->assertNull(CommandRegistry::get('unknown:command'));
    }

    public function testAllReturnsAllCommands(): void
    {
        $commands = CommandRegistry::all();
        $this->assertArrayHasKey('help', $commands);
        $this->assertArrayHasKey('migrate', $commands);
        $this->assertArrayHasKey('serve', $commands);
    }
}
```

- [ ] **Step 5: Run test to verify it fails**

Run: `vendor/bin/phpunit test/Engine/CommandRegistryTest.php`
Expected: PASS (no implementation needed beyond the class existing with static methods)

- [ ] **Step 6: Commit**

```bash
git add src/Simple/Engine/contracts/CommandInterface.php src/Simple/Engine/CommandRegistry.php src/Simple/Engine/Commands/.gitkeep src/Simple/Engine/Stubs/.gitkeep test/Engine/CommandRegistryTest.php
git commit -m "feat: add CommandInterface, CommandRegistry, and directory structure"
```

---

### Task 2: Create Stub Files

**Files:**
- Create: `src/Simple/Engine/Stubs/controller.stub`
- Create: `src/Simple/Engine/Stubs/controller-resource.stub`
- Create: `src/Simple/Engine/Stubs/controller-resource-model.stub`
- Create: `src/Simple/Engine/Stubs/model.stub`
- Create: `src/Simple/Engine/Stubs/observer.stub`
- Create: `src/Simple/Engine/Stubs/request.stub`

- [ ] **Step 1: Create `controller.stub`** (basic controller, from Console.php lines 182-196)

```
<?php

namespace App\Controllers;

Use Simple\Request;

class {{className}} extends Controller
{

    public function index()
    {

    }

}
```

- [ ] **Step 2: Create `controller-resource.stub`** (CRUD controller with `-r` flag, from Console.php lines 101-178)

```
<?php

namespace App\Controllers;

Use Simple\Request;

class {{className}} extends Controller
{

    /**
     * the index action can be use to show all the records
     *
     * @return void
     */
    public function index()
    {

    }

    /**
     * Shows the from for creating {{className}}
     *
     * @return void
     */
    public function create()
    {

    }

    /**
     * Store the data from {{className}} POST form
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {

    }

    /**
     * Show the edit form for {{className}}
     *
     * @param Request $request
     * @return void
     */
    public function edit(Request $request)
    {
        $id = $request->route('id');

    }

    /**
     * Update the existing record
     *
     * @param Request $request
     * @return void
     */
    public function update(Request $request)
    {
        $id = $request->route('id');

    }

    /**
     * Delete the record
     *
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request)
    {
        $id = $request->route('id');

    }

}
```

- [ ] **Step 3: Create `model.stub`** (from Console.php lines 337-364)

```
<?php

namespace App\Models;

Use Simple\Model;

class {{className}} extends Model
{
    /**
     * $table - table name using by this model
     *
     * @var string
     */
    protected $table = '{{tableName}}';

    /**
     * Fillables - the columns in you $table
     *
     * @var array
     */
    protected $fillable = [];

    /**
     *  This is generated {{className}} model.
     *  It is recommended that you put all queries here.
     *  Create Something great!
     */
}
```

- [ ] **Step 4: Create `observer.stub`** (from Console.php lines 234-251)

```
<?php

namespace App\Observers;

use Simple\Model;

class {{className}}Observer
{
    /**
     * Define your observers here with the following methods:
     * created, updated, deleted, restored, forceDeleted
     *
     * @param Model $model
     * @return void
     */

}
```

- [ ] **Step 5: Create `request.stub`** (from Console.php lines 277-312)

```
<?php

namespace App\Requests;

use Simple\Validation\FormRequest;

class {{className}} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'field' => 'required|valid_email',
        ];
    }

    public function messages(): array
    {
        return [
            // 'field' => [
            //     'required' => 'The {field} is required.',
            // ],
        ];
    }

    public function fields(): array
    {
        return [
            // 'field' => 'Friendly Field Name',
        ];
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add src/Simple/Engine/Stubs/
git commit -m "feat: extract inline stub templates to .stub files"
```

---

### Task 3: Simple Command Classes (CacheClear, SessionDestroy, Help, KeyGenerate, RouteList, Serve)

**Files:**
- Create: `src/Simple/Engine/Commands/CacheClearCommand.php`
- Create: `src/Simple/Engine/Commands/SessionDestroyCommand.php`
- Create: `src/Simple/Engine/Commands/HelpCommand.php`
- Create: `src/Simple/Engine/Commands/KeyGenerateCommand.php`
- Create: `src/Simple/Engine/Commands/RouteListCommand.php`
- Create: `src/Simple/Engine/Commands/ServeCommand.php`

- [ ] **Step 1: Create `CacheClearCommand.php`** (moved from Console.php `clearCacheViews()`, lines 715-733)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class CacheClearCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $cacheDir = './storage/framework/cache/views';
        if (!is_dir($cacheDir)) {
            return ['type' => 'error', 'message' => 'Views cache directory not found.'];
        }
        $files = glob($cacheDir . '/*');
        if ($files === false) {
            return ['type' => 'error', 'message' => 'Error reading views cache directory.'];
        }
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return ['type' => 'success', 'message' => 'Views cache cleared successfully.'];
    }
}
```

- [ ] **Step 2: Create `SessionDestroyCommand.php`** (moved from Console.php inline, lines 53-56)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;
use Simple\Session;

class SessionDestroyCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        Session::destroy();
        return ['type' => 'success', 'message' => 'All sessions is destroyed.'];
    }
}
```

- [ ] **Step 3: Create `HelpCommand.php`** (moved from Console.php `cliHelp()`, lines 735-756)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;
use Simple\Engine\CommandRegistry;

class HelpCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $output = new ConsoleOutput;
        echo PHP_EOL;
        echo ">> php cli + command" . PHP_EOL;
        echo PHP_EOL;
        echo "AVAILABLE COMMANDS:" . PHP_EOL;
        echo $output->print_o(" serve", 'green', 'black') . " This creates a webserver and host you application" . PHP_EOL;
        echo $output->print_o("       options: host port=8080", 'blue', 'black') . " You can set the host and port(optional)" . PHP_EOL;
        echo $output->print_o(" route:list", 'green', 'black') . " Display your route aliases" . PHP_EOL;
        echo $output->print_o(" key:generate", 'green', 'black') . " This creates key for Encryption and Decryption feature" . PHP_EOL;
        echo $output->print_o(" make:controller ControllerName", 'green', 'black') . " This creates a controller in app/Controllers" . PHP_EOL;
        echo $output->print_o("       options: -r or -rm", 'blue', 'black') . " Make the controller a resource(for CRUD), also creates the model automatically" . PHP_EOL;
        echo $output->print_o(" make:model", 'green', 'black') . " This creates a model in app/Models" . PHP_EOL;
        echo $output->print_o(" make:observer", 'green', 'black') . " This creates an observer in app/Observers" . PHP_EOL;
        echo $output->print_o(" make:auth", 'green', 'black') . " This creates a authentication scaffoldings for your application" . PHP_EOL;
        echo $output->print_o(" make:request RequestName", 'green', 'black') . " This creates a form request class for validation" . PHP_EOL;
        echo $output->print_o(" user:seed", 'green', 'black') . " Insert data to users table" . PHP_EOL;
        echo $output->print_o(" migrate sqlfilename", 'green', 'black') . " Migrate the sqlfiles in database folder(for mysql only)" . PHP_EOL;
        echo $output->print_o(" migrate users", 'green', 'black') . " This creates users table in you database(for sqlite and mysql)" . PHP_EOL;
        echo $output->print_o(" migrate -c \"your_query\"", 'green', 'black') . " Communicate with sqlite database" . PHP_EOL;
        echo $output->print_o(" session:destroy", 'green', 'black') . " Destroys all active session" . PHP_EOL;
        echo $output->print_o(" cache:clear", 'green', 'black') . " Clears the Twig views cache" . PHP_EOL;
        echo PHP_EOL;
        return null;
    }
}
```

- [ ] **Step 4: Create `KeyGenerateCommand.php`** (moved from Console.php `keyGenerate()`, lines 683-699)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;
use Simple\Security\Encryption;

class KeyGenerateCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $key = Encryption::generateKey();
        $envFile = './.env';
        if (!file_exists($envFile)) {
            return ['type' => 'error', 'message' => 'Failed to generate application key: .env not found'];
        }
        $contents = file_get_contents($envFile);
        if (preg_match('/^APP_KEY=.*$/m', $contents)) {
            $contents = preg_replace('/^APP_KEY=.*$/m', "APP_KEY=$key", $contents);
        } else {
            $contents .= PHP_EOL . "APP_KEY=$key" . PHP_EOL;
        }
        file_put_contents($envFile, $contents);
        return ['type' => 'success', 'message' => 'Application Key Generated Successfully!'];
    }
}
```

- [ ] **Step 5: Create `RouteListCommand.php`** (moved from Console.php `routeList()`, lines 701-713)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;
use Simple\Routing\Router;

class RouteListCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        require './app/Routes.php';
        $output = new ConsoleOutput;
        $compile_routes = Router::compiledRoutes();
        echo '-----------------------------------------------------------------' . PHP_EOL;
        foreach ($compile_routes as $key => $val) {
            echo $output->print_o($val['request_method'] . "  '$key'", 'green', 'black') . ' => ' . $val['url'] . PHP_EOL;
            echo '-----------------------------------------------------------------' . PHP_EOL;
        }
        echo $output->print_o(" You have " . count($compile_routes) . " route aliases in your Routes.php", 'black', 'light_gray') . PHP_EOL;
        return null;
    }
}
```

- [ ] **Step 6: Create `ServeCommand.php`** (moved from Console.php `serve()`, lines 669-678)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;

class ServeCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $output = new ConsoleOutput;
        $host = $args[0] ?? 'localhost';
        $port = $args[1] ?? '8000';
        if ($port !== null && str_starts_with($port, 'port=')) {
            $port = substr($port, 5);
        }
        $command = "php -S $host:$port -t public/";
        echo $output->print_o("Simply Development Server started at: http://$host:$port" . PHP_EOL, 'green', 'white');
        echo $output->print_o("Press CTRL+C to cancel" . PHP_EOL, 'green', 'black');
        exec($command, $worked, $output);
        return null;
    }
}
```

- [ ] **Step 7: Commit**

```bash
git add src/Simple/Engine/Commands/CacheClearCommand.php src/Simple/Engine/Commands/SessionDestroyCommand.php src/Simple/Engine/Commands/HelpCommand.php src/Simple/Engine/Commands/KeyGenerateCommand.php src/Simple/Engine/Commands/RouteListCommand.php src/Simple/Engine/Commands/ServeCommand.php
git commit -m "feat: add simple command classes (cache:clear, session:destroy, help, key:generate, route:list, serve)"
```

---

### Task 4: Code Generation Command Classes (MakeModel, MakeObserver, MakeRequest)

**Files:**
- Create: `src/Simple/Engine/Commands/MakeModelCommand.php`
- Create: `src/Simple/Engine/Commands/MakeObserverCommand.php`
- Create: `src/Simple/Engine/Commands/MakeRequestCommand.php`

- [ ] **Step 1: Create `MakeModelCommand.php`** (moved from Console.php `createModel()`, lines 333-380 + `convertToStudlyCaps()`, lines 387-390)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeModelCommand implements CommandInterface
{
    private string $modelPath = 'app/Models/';

    public function handle(array $args): ?array
    {
        $name = $args[0] ?? null;
        if (!$name) {
            return ['type' => 'error', 'message' => 'Model name must be defined'];
        }
        $name = self::convertToStudlyCaps($name);
        $stub = file_get_contents(__DIR__ . '/../Stubs/model.stub');
        $stub = str_replace(
            ['{{className}}', '{{tableName}}'],
            [$name, strtolower($name) . 's'],
            $stub
        );
        if (file_exists("$this->modelPath$name.php")) {
            return ['type' => 'error', 'message' => "$name Model is already exist!"];
        }
        $dir = dirname("$this->modelPath$name.php");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents("$this->modelPath$name.php", $stub);
        return ['type' => 'success', 'message' => "Model $name created successfuly"];
    }

    private static function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
}
```

- [ ] **Step 2: Create `MakeObserverCommand.php`** (moved from Console.php `createObserver()`, lines 231-271)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeObserverCommand implements CommandInterface
{
    private string $observerPath = 'app/Observers/';

    public function handle(array $args): ?array
    {
        $model = $args[0] ?? null;
        if (!$model) {
            return ['type' => 'error', 'message' => 'Model name must be defined'];
        }
        $model = self::convertToStudlyCaps($model);
        $stub = file_get_contents(__DIR__ . '/../Stubs/observer.stub');
        $stub = str_replace('{{className}}', $model, $stub);
        $filePath = "{$this->observerPath}{$model}Observer.php";
        if (file_exists($filePath)) {
            return ['type' => 'error', 'message' => "$model Observer is already exist!"];
        }
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, $stub);
        return ['type' => 'success', 'message' => "Observer $model created successfuly"];
    }

    private static function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
}
```

- [ ] **Step 3: Create `MakeRequestCommand.php`** (moved from Console.php `createRequest()`, lines 273-331)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeRequestCommand implements CommandInterface
{
    private string $requestPath = 'app/Requests/';

    public function handle(array $args): ?array
    {
        $name = $args[0] ?? null;
        if (!$name) {
            return ['type' => 'error', 'message' => 'Request name must be defined'];
        }
        $name = self::convertToStudlyCaps($name);
        $stub = file_get_contents(__DIR__ . '/../Stubs/request.stub');
        $stub = str_replace('{{className}}', $name, $stub);
        $dir = $this->requestPath;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filePath = "$dir$name.php";
        if (file_exists($filePath)) {
            return ['type' => 'error', 'message' => "$name Request is already exist!"];
        }
        file_put_contents($filePath, $stub);
        return ['type' => 'success', 'message' => "Request $name created successfully"];
    }

    private static function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add src/Simple/Engine/Commands/MakeModelCommand.php src/Simple/Engine/Commands/MakeObserverCommand.php src/Simple/Engine/Commands/MakeRequestCommand.php
git commit -m "feat: add code generation command classes (model, observer, request)"
```

---

### Task 5: MakeControllerCommand

**Files:**
- Create: `src/Simple/Engine/Commands/MakeControllerCommand.php`

- [ ] **Step 1: Create `MakeControllerCommand.php`** (moved from Console.php `createController()`, lines 92-229 + `convertToStudlyCaps()`)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeControllerCommand implements CommandInterface
{
    private string $controllerPath = './app/Controllers/';
    private string $modelPath = 'app/Models/';

    public function handle(array $args): ?array
    {
        $name = $args[0] ?? null;
        $option = $args[1] ?? null;
        if (!$name) {
            return ['type' => 'error', 'message' => 'Controller name must be defined'];
        }
        if (!preg_match("/controller$/i", $name)) {
            $name = $name . 'Controller';
        }
        $name = self::convertToStudlyCaps($name);
        $filePath = "$this->controllerPath$name.php";

        if ($option === '-r' || $option === '-rm') {
            $stubFile = $option === '-rm'
                ? __DIR__ . '/../Stubs/controller-resource-model.stub'
                : __DIR__ . '/../Stubs/controller-resource.stub';
            $stub = file_get_contents($stubFile);
            $stub = str_replace('{{className}}', $name, $stub);
        } else {
            $stub = file_get_contents(__DIR__ . '/../Stubs/controller.stub');
            $stub = str_replace('{{className}}', $name, $stub);
        }

        if ($option === '-rm' || $option === '-m') {
            $modelName = str_replace('Controller', '', $name);
            $modelClass = self::convertToStudlyCaps($modelName);
            $modelStub = file_get_contents(__DIR__ . '/../Stubs/model.stub');
            $modelStub = str_replace(
                ['{{className}}', '{{tableName}}'],
                [$modelClass, strtolower($modelClass) . 's'],
                $modelStub
            );
            $modelFilePath = "{$this->modelPath}$modelClass.php";
            if (!file_exists($modelFilePath)) {
                $dir = dirname($modelFilePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($modelFilePath, $modelStub);
            }
        }

        if (file_exists($filePath)) {
            return ['type' => 'error', 'message' => "$name Controller is already exist!"];
        }
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, $stub);
        return ['type' => 'success', 'message' => "Controller $name created successfuly"];
    }

    private static function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
}
```

Note: This uses `controller-resource.stub` and `controller-resource-model.stub` which share the same content (the CRUD controller template). The `-rm` flag additionally creates the model after creating the controller. Since both use the same stub content, `controller-resource-model.stub` will be the same as `controller-resource.stub` — the difference is in the command logic.

- [ ] **Step 2: Create composite stub files**

Create `controller-resource-model.stub` with same content as `controller-resource.stub` (the CRUD controller template):

```bash
cp src/Simple/Engine/Stubs/controller-resource.stub src/Simple/Engine/Stubs/controller-resource-model.stub
```

- [ ] **Step 3: Commit**

```bash
git add src/Simple/Engine/Commands/MakeControllerCommand.php src/Simple/Engine/Stubs/controller-resource-model.stub
git commit -m "feat: add MakeControllerCommand with stub-based code generation"
```

---

### Task 6: Complex Command Classes (MakeAuth, Seed, Migrate)

**Files:**
- Create: `src/Simple/Engine/Commands/MakeAuthCommand.php`
- Create: `src/Simple/Engine/Commands/SeedCommand.php`
- Create: `src/Simple/Engine/Commands/MigrateCommand.php`

- [ ] **Step 1: Create `MakeAuthCommand.php`** (moved from Console.php `makeAuth()`, lines 516-586)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeAuthCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/controller/*.stub') as $filename) {
            $dest = "app/Controllers/Auth/" . str_replace('.stub', '.php', basename($filename));
            if (!file_exists('app/Controllers/Auth')) {
                mkdir('app/Controllers/Auth', 0777, true);
            }
            copy($filename, $dest);
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/helper/*.stub') as $filename) {
            $dest = "app/Helper/Auth/" . str_replace('.stub', '.php', basename($filename));
            if (!file_exists('app/Helper/Auth')) {
                mkdir('app/Helper/Auth', 0777, true);
            }
            copy($filename, $dest);
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/model/*.stub') as $filename) {
            $dest = "app/Models/" . str_replace('.stub', '.php', basename($filename));
            copy($filename, $dest);
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/Views/Auth/*.html') as $filename) {
            $dest = "app/Views/auth/" . basename($filename);
            if (!file_exists('app/Views/auth')) {
                mkdir('app/Views/auth', 0777, true);
            }
            copy($filename, $dest);
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/Views/layouts/*.html') as $filename) {
            $dest = "app/Views/layouts/" . basename($filename);
            if (!file_exists($dest)) {
                copy($filename, $dest);
            }
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/request/*.stub') as $filename) {
            $dest = "app/Requests/" . str_replace('.stub', '.php', basename($filename));
            copy($filename, $dest);
        }

        $routeFile = './vendor/simplyphp/framework/src/AuthScaffolding/routes.simply';
        $file = file_get_contents($routeFile, FILE_USE_INCLUDE_PATH);
        $mainRoute = "./app/Routes.php";
        file_put_contents($mainRoute, PHP_EOL . $file, FILE_APPEND | LOCK_EX);

        return ['type' => 'success', 'message' => 'Auth scaffolding created successfully'];
    }
}
```

- [ ] **Step 2: Create `SeedCommand.php`** (moved from Console.php `seed()`, lines 588-663)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;
use Simple\Config;
use mysqli;
use PDO;

class SeedCommand implements CommandInterface
{
    private ConsoleOutput $output;

    public function __construct()
    {
        $this->output = new ConsoleOutput;
    }

    public function handle(array $args): ?array
    {
        Config::load('./app/Config');

        $dbname = Config::get('database.name', '');
        $dbuser = Config::get('database.user', '');
        $dbpass = Config::get('database.pass', '');
        $dbserver = Config::get('database.server', 'localhost');

        start:
        echo "seeding..." . PHP_EOL;
        echo $this->output->print_o(" Enter name: ", "white", "cyan");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $name = trim($line);
        fclose($handle);

        echo $this->output->print_o(" Enter Email: ", "white", "magenta");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $email = trim($line);
        fclose($handle);

        echo $this->output->print_o(" Enter password: ", "black", "light_gray");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $password = trim($line);
        fclose($handle);
        $password = password_hash($password, PASSWORD_BCRYPT);

        $dbEngine = Config::get('database.engine', 'mysql');
        if ($dbEngine == 'mysqli' || $dbEngine == 'mysql') {
            $db = new mysqli($dbserver, $dbuser, $dbpass, $dbname);
            $stmt = $db->prepare("INSERT INTO users(name,email,password_hash) VALUES(?,?,?)") or die($db->error);
            $stmt->bind_param("sss", $name, $email, $password);
            if ($stmt->execute()) {
                echo $this->output->print_o(PHP_EOL . " Seeding successfull ", "black", "green");
            } else {
                echo $this->output->print_o(" Seeding failed: $stmt->error", "white", "red");
            }
        } elseif ($dbEngine == 'sqlite') {
            try {
                $table = 'users';
                $db = new PDO("sqlite:" . "./database/database.db");
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sql = "INSERT INTO users(name, email, password_hash) VALUES (?,?,?)";
                $stmt = $db->prepare($sql);
                $data = array($name, $email, $password);
                echo $sql;
                echo PHP_EOL;
                $stmt->execute($data);
                echo $this->output->print_o(PHP_EOL . " Seeding successfull ", "black", "green");
                unset($stmt);
                $db = null;
            } catch (\Exception $e) {
                echo "Unable to connect" . PHP_EOL;
                echo $e->getMessage();
                exit;
            }
        }

        echo PHP_EOL . "Do you want to seed another entry? (yes|no): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $ans = trim($line);
        fclose($handle);
        if ($ans == 'yes') {
            goto start;
        }
        return null;
    }
}
```

- [ ] **Step 3: Create `MigrateCommand.php`** (moved from Console.php `migrate()`, lines 392-514)

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;
use Simple\Config;
use PDO;

class MigrateCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        Config::load('./app/Config');
        $directory = './database';
        $imports = scandir($directory);
        $dbEngine = Config::get('database.engine', 'mysql');
        $file = $args[0] ?? null;
        $com = $args[1] ?? null;

        if ($dbEngine == 'mysql' || $dbEngine == 'mysqli') {
            $mysqlDatabaseName = Config::get('database.name', '');
            $mysqlUserName = Config::get('database.user', '');
            $mysqlPassword = Config::get('database.pass', '');
            $mysqlHostName = Config::get('database.server', 'localhost');

            if ($file == null) {
                foreach ($imports as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $filePath = $directory . '/' . $file;
                    if (!is_dir($filePath)) {
                        echo "Importing => $filePath" . PHP_EOL;
                        $command = 'mysql -h' . $mysqlHostName . ' -u' . $mysqlUserName . ' --password="' . $mysqlPassword . '" ' . $mysqlDatabaseName . ' < ' . $filePath . ' 2>&1 | grep -v "Warning: Using a password"';
                        $output = [];
                        exec($command, $output, $worked);
                        switch ($worked) {
                            case 0:
                                echo 'success: file ' . $filePath . ' successfully imported ' . PHP_EOL;
                                break;
                            case 1:
                                echo 'error: There was an error during the import ' . PHP_EOL;
                                break;
                        }
                    }
                }
                return null;
            } else {
                $mysqlImportFilename = "./database/$file.sql";
                $command = 'mysql -h' . $mysqlHostName . ' -u' . $mysqlUserName . ' -p' . $mysqlPassword . ' ' . $mysqlDatabaseName . ' < ' . $mysqlImportFilename;
                $output = [];
                exec($command, $output, $worked);
                switch ($worked) {
                    case 0:
                        return ['type' => 'success', 'message' => 'Import file ' . $mysqlImportFilename . ' successfully imported to database ' . $mysqlDatabaseName];
                    case 1:
                        return ['type' => 'error', 'message' => 'There was an error during the import'];
                }
            }
        } elseif ($dbEngine == 'sqlite') {
            try {
                $db = new PDO("sqlite:" . "./database/database.db");
                $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                if ($file == '-c') {
                    $sql = $com;
                } elseif ($file == 'users') {
                    $sql = "CREATE TABLE IF NOT EXISTS users(
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT NOT NULL,
                        email TEXT NOT NULL UNIQUE,
                        password_hash TEXT NOT NULL,
                        reset_token TEXT NULL,
                        email_verified_at TEXT NULL,
                        created_at TEXT NULL,
                        updated_at TEXT NULL)";
                } else {
                    return ['type' => 'error', 'message' => 'Unknown migrate option for sqlite'];
                }

                echo PHP_EOL;
                $command_ = explode(' ', $sql);
                if (strtoupper($command_[0]) === "SELECT") {
                    $res = $db->query($sql);
                    $i = 0;
                    $col = [];
                    $rows = [];
                    $v = [];
                    $table = new \LucidFrame\Console\ConsoleTable();
                    foreach ($res as $row) {
                        $i++;
                        foreach ($row as $key => $val) {
                            if ($i == 1) {
                                $col[] .= $key;
                            }
                            $v[] = $val;
                        }
                        $rows[$i] = $v;
                        unset($v);
                    }
                    $table->setHeaders($col);
                    foreach ($rows as $r) {
                        $table->addRow($r);
                    }
                    $table->display();
                } else {
                    $c = $db->exec($sql);
                    print("Command successfull. $c affected rows.\n");
                }
            } catch (\Exception $e) {
                echo "Unable to connect" . PHP_EOL;
                echo $e->getMessage();
                exit;
            }
        }
        return null;
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add src/Simple/Engine/Commands/MakeAuthCommand.php src/Simple/Engine/Commands/SeedCommand.php src/Simple/Engine/Commands/MigrateCommand.php
git commit -m "feat: add complex command classes (make:auth, user:seed, migrate)"
```

---

### Task 7: Refactor Console.php

**Files:**
- Modify: `src/Simple/Engine/Console.php` (complete rewrite)

- [ ] **Step 1: Replace entire Console.php**

New file replaces the 757-line class with a ~65-line thin dispatcher:

```php
<?php

namespace Simple\Engine;

use Simple\Engine\ConsoleOutput as co;
use Simple\Engine\Contracts\CommandInterface;
use Simple\Session;

class Console
{
    private array $argv;
    private ?string $status;
    private co $output;

    public function __construct($argc, $argv)
    {
        $this->status = null;
        $this->argv = $argv ?? [];
        $this->output = new co;
    }

    public function consoleRun(): void
    {
        $name = $this->argv[1] ?? null;
        if (!$name) {
            $this->status = 'error: No command provided.' . PHP_EOL;
            return;
        }

        $class = CommandRegistry::get($name);
        if (!$class) {
            $this->status = 'error: ===== Command not found. =====' . PHP_EOL;
            return;
        }

        $command = new $class;
        if (!$command instanceof CommandInterface) {
            $this->status = 'error: Invalid command class.' . PHP_EOL;
            return;
        }

        $result = $command->handle(array_slice($this->argv, 2));
        if ($result !== null) {
            $this->status = $result['type'] . ': ' . $result['message'] . PHP_EOL;
        }
    }

    public function print_status(): void
    {
        if ($this->status === null) {
            return;
        }
        $parts = explode(':', $this->status);
        if ($parts[0] === 'error') {
            echo $this->output->print_o($parts[1], "white", "red");
        } elseif ($parts[0] === 'success') {
            echo $this->output->print_o($parts[1], "black", "green");
        }
    }
}
```

- [ ] **Step 2: Remove unused imports from original Console.php**

The original imports `use Simple\Engine\ConsoleOutput as co;`, `use mysqli;`, `use Simple\Session;`, `use PDO;`. The new Console only needs `ConsoleOutput as co` and `Session`. Remove `mysqli` and `PDO` imports since those are handled by the command classes now.

- [ ] **Step 3: Commit**

```bash
git add src/Simple/Engine/Console.php
git commit -m "refactor: Console.php becomes thin dispatcher using CommandRegistry"
```

---

### Task 8: Verify

**Files:** None (verification only)

- [ ] **Step 1: Run existing tests**

```bash
vendor/bin/phpunit
```

Expected: All existing tests pass (no behavioral changes to any framework functionality).

- [ ] **Step 2: Run CommandRegistry test**

```bash
vendor/bin/phpunit test/Engine/CommandRegistryTest.php
```

Expected: All 3 tests pass.

- [ ] **Step 3: Verify autoload works**

```bash
php -r "require 'vendor/autoload.php'; echo 'OK';"
```

Expected: "OK" — no autoload issues from new directory structure.

- [ ] **Step 4: Verify Console still works**

```bash
# From a project that uses the framework (e.g., simple-php or frm-testing):
php cli help
php cli route:list
```

Expected: Same output as before refactoring.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "chore: final verification and cleanup"
```

---
