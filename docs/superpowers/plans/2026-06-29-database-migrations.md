# Database Migrations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the current raw-SQL migration system with class-based migrations using Eloquent Schema builder, a custom MigrationRunner, and CLI commands for make:migration, migrate, and migrate:fresh.

**Architecture:** A `Migration` base class defines the up/down contract. A `MigrationRunner` scans `database/migrations/`, tracks runs in a `migrations` table, and executes pending migrations. Three new CLI commands handle creation, running, and freshing. The old `MigrateCommand.php` is completely rewritten.

**Tech Stack:** PHP 8.0+, PHPUnit 10, PSR-4 autoloading, illuminate/database (Schema/Blueprint already available)

---

### Task 1: Directory Structure + Migration Base Class + Test

**Files:**
- Create: `src/Simple/Database/Migrations/Migration.php`
- Create: `src/Simple/Database/Migrations/.gitkeep`
- Create: `test/Database/Migrations/MigrationTest.php`

- [ ] **Step 1: Create directory structure**

```bash
mkdir -p src/Simple/Database/Migrations
touch src/Simple/Database/Migrations/.gitkeep
```

- [ ] **Step 2: Write the failing test for Migration base class**

```php
<?php

namespace Simple\Tests\Database\Migrations;

use PHPUnit\Framework\TestCase;
use Simple\Database\Migrations\Migration;

class MigrationTest extends TestCase
{
    public function testMigrationIsAbstract(): void
    {
        $ref = new \ReflectionClass(Migration::class);
        $this->assertTrue($ref->isAbstract());
    }

    public function testMigrationHasAbstractUpMethod(): void
    {
        $ref = new \ReflectionMethod(Migration::class, 'up');
        $this->assertTrue($ref->isAbstract());
        $this->assertSame('void', $ref->getReturnType()->getName());
    }

    public function testMigrationHasAbstractDownMethod(): void
    {
        $ref = new \ReflectionMethod(Migration::class, 'down');
        $this->assertTrue($ref->isAbstract());
        $this->assertSame('void', $ref->getReturnType()->getName());
    }

    public function testConcreteMigrationCanBeInstantiated(): void
    {
        $migration = new class extends Migration {
            public function up(): void {}
            public function down(): void {}
        };
        $this->assertInstanceOf(Migration::class, $migration);
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `vendor/bin/phpunit test/Database/Migrations/MigrationTest.php`
Expected: FAIL — class not found (doesn't exist yet)

- [ ] **Step 4: Create Migration base class**

```php
<?php

namespace Simple\Database\Migrations;

abstract class Migration
{
    abstract public function up(): void;
    abstract public function down(): void;
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit test/Database/Migrations/MigrationTest.php`
Expected: PASS — 4 tests

- [ ] **Step 6: Commit**

```bash
git add src/Simple/Database/Migrations/ test/Database/Migrations/MigrationTest.php
git commit -m "feat: add Migration base class with up/down contract"
```

---

### Task 2: Migration Stub File

**Files:**
- Create: `src/Simple/Engine/Stubs/migration.stub`

- [ ] **Step 1: Create the migration stub**

```
<?php

use Simple\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{{tableName}}', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{{tableName}}');
    }
};
```

- [ ] **Step 2: Commit**

```bash
git add src/Simple/Engine/Stubs/migration.stub
git commit -m "feat: add migration stub for make:migration"
```

---

### Task 3: MigrationRunner + Test

**Files:**
- Create: `src/Simple/Database/Migrations/MigrationRunner.php`
- Create: `test/Database/Migrations/MigrationRunnerTest.php`

- [ ] **Step 1: Write the failing test for MigrationRunner**

```php
<?php

namespace Simple\Tests\Database\Migrations;

use PHPUnit\Framework\TestCase;
use Simple\Database\Migrations\MigrationRunner;

class MigrationRunnerTest extends TestCase
{
    private string $migrationsDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->migrationsDir = sys_get_temp_dir() . '/migrations-runner-test-' . getmypid();
        mkdir($this->migrationsDir, 0777, true);

        $this->originalCwd = getcwd();
        chdir($this->migrationsDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        $this->rrmdir($this->migrationsDir);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testRunWithNoMigrationsReturnsEmptyArray(): void
    {
        $runner = new MigrationRunner($this->migrationsDir, ':memory:');
        $result = $runner->run();
        $this->assertSame([], $result);
    }

    public function testRunExecutesPendingMigration(): void
    {
        file_put_contents($this->migrationsDir . '/2026_01_01_000001_test.php', '<?php
            use Simple\Database\Migrations\Migration;
            use Illuminate\Support\Facades\Schema;
            use Illuminate\Database\Schema\Blueprint;
            return new class extends Migration {
                public function up(): void {
                    Schema::create("test_table", function (Blueprint $t) {
                        $t->id();
                        $t->string("name");
                    });
                }
                public function down(): void {
                    Schema::dropIfExists("test_table");
                }
            };
        ');

        $runner = new MigrationRunner($this->migrationsDir, ':memory:');
        $result = $runner->run();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('test.php', $result[0]);
    }

    public function testRunSkipsAlreadyExecutedMigrations(): void
    {
        file_put_contents($this->migrationsDir . '/2026_01_01_000001_test.php', '<?php
            use Simple\Database\Migrations\Migration;
            use Illuminate\Support\Facades\Schema;
            use Illuminate\Database\Schema\Blueprint;
            return new class extends Migration {
                public function up(): void { Schema::create("test_table", function (Blueprint $t) { $t->id(); }); }
                public function down(): void { Schema::dropIfExists("test_table"); }
            };
        ');

        $runner = new MigrationRunner($this->migrationsDir, ':memory:');
        $runner->run();
        $result = $runner->run();

        $this->assertSame([], $result);
    }

    public function testFreshDropsTablesAndReRuns(): void
    {
        file_put_contents($this->migrationsDir . '/2026_01_01_000001_test.php', '<?php
            use Simple\Database\Migrations\Migration;
            use Illuminate\Support\Facades\Schema;
            use Illuminate\Database\Schema\Blueprint;
            return new class extends Migration {
                public function up(): void { Schema::create("test_table", function (Blueprint $t) { $t->id(); }); }
                public function down(): void { Schema::dropIfExists("test_table"); }
            };
        ');

        $runner = new MigrationRunner($this->migrationsDir, ':memory:');
        $runner->run();
        $result = $runner->fresh();

        $this->assertCount(1, $result);
    }

    public function testRunExecutesInFilenameOrder(): void
    {
        file_put_contents($this->migrationsDir . '/2026_02_01_000002_second.php', '<?php
            use Simple\Database\Migrations\Migration;
            use Illuminate\Support\Facades\Schema;
            use Illuminate\Database\Schema\Blueprint;
            return new class extends Migration {
                public function up(): void { Schema::create("second_table", function (Blueprint $t) { $t->id(); }); }
                public function down(): void { Schema::dropIfExists("second_table"); }
            };
        ');
        file_put_contents($this->migrationsDir . '/2026_01_01_000001_first.php', '<?php
            use Simple\Database\Migrations\Migration;
            use Illuminate\Support\Facades\Schema;
            use Illuminate\Database\Schema\Blueprint;
            return new class extends Migration {
                public function up(): void { Schema::create("first_table", function (Blueprint $t) { $t->id(); }); }
                public function down(): void { Schema::dropIfExists("first_table"); }
            };
        ');

        $runner = new MigrationRunner($this->migrationsDir, ':memory:');
        $result = $runner->run();

        $this->assertCount(2, $result);
        $this->assertStringContainsString('first', $result[0]);
        $this->assertStringContainsString('second', $result[1]);
    }

    public function testBatchIncrementsOnEachRun(): void
    {
        file_put_contents($this->migrationsDir . '/2026_01_01_000001_test.php', '<?php
            use Simple\Database\Migrations\Migration;
            use Illuminate\Support\Facades\Schema;
            use Illuminate\Database\Schema\Blueprint;
            return new class extends Migration {
                public function up(): void { Schema::create("test_table", function (Blueprint $t) { $t->id(); }); }
                public function down(): void { Schema::dropIfExists("test_table"); }
            };
        ');

        $runner = new MigrationRunner($this->migrationsDir, ':memory:');

        $runner->run();

        unlink($this->migrationsDir . '/2026_01_01_000001_test.php');
        file_put_contents($this->migrationsDir . '/2026_02_01_000002_second.php', '<?php
            use Simple\Database\Migrations\Migration;
            use Illuminate\Support\Facades\Schema;
            use Illuminate\Database\Schema\Blueprint;
            return new class extends Migration {
                public function up(): void { Schema::create("second_table", function (Blueprint $t) { $t->id(); }); }
                public function down(): void { Schema::dropIfExists("second_table"); }
            };
        ');

        $result = $runner->run();
        $this->assertCount(1, $result);
    }

    public function testRunThrowsForNonMigrationFile(): void
    {
        file_put_contents($this->migrationsDir . '/2026_01_01_000001_bad.php', '<?php return "not a migration"; ');

        $runner = new MigrationRunner($this->migrationsDir, ':memory:');

        $this->expectException(\RuntimeException::class);
        $runner->run();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit test/Database/Migrations/MigrationRunnerTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create MigrationRunner**

```php
<?php

namespace Simple\Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Simple\Config;
use Simple\Database\DB;

class MigrationRunner
{
    private string $path;
    private string $connection;

    public function __construct(
        string $path = './database/migrations/',
        string $connection = ''
    ) {
        $this->path = rtrim($path, '/') . '/';
        $this->connection = $connection;
    }

    /**
     * @return string[] List of migration filenames that were run
     */
    public function run(): array
    {
        $this->ensureConnected();
        $this->ensureTrackingTable();
        $ran = [];

        $files = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();

        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $executed, true)) {
                continue;
            }

            $migration = $this->loadMigration($file);
            $migration->up();

            DB::table('migrations')->insert([
                'migration' => $filename,
                'batch' => $this->getNextBatch(),
            ]);

            $ran[] = $filename;
        }

        return $ran;
    }

    /**
     * @return string[] List of migration filenames that were run
     */
    public function fresh(): array
    {
        $this->ensureConnected();

        $driver = Config::get('database.engine', 'sqlite');
        $schema = DB::connection()->getSchemaBuilder();
        $prefix = DB::connection()->getTablePrefix();

        if ($driver === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name != 'migrations'");
            $tableNames = array_map(fn($t) => $t->name, $tables);
        } else {
            $tables = DB::select('SHOW TABLES');
            $firstKey = array_keys((array)$tables[0])[0];
            $tableNames = array_map(fn($t) => $t->$firstKey, $tables);
            $tableNames = array_filter($tableNames, fn($n) => $n !== ($prefix . 'migrations'));
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tableNames as $table) {
            $schema->drop($table);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->resetTrackingTable();

        return $this->run();
    }

    private function ensureConnected(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $capsule = new Capsule;
            $dbConfig = [
                'driver'    => Config::get('database.engine', 'sqlite'),
                'host'      => Config::get('database.server', 'localhost'),
                'database'  => $this->connection ?: Config::get('database.name', './database/database.db'),
                'username'  => Config::get('database.user', 'root'),
                'password'  => Config::get('database.pass', ''),
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
            ];

            if ($dbConfig['driver'] === 'sqlite'
                && $dbConfig['database'] !== ':memory:'
                && $dbConfig['database'][0] !== '/'
            ) {
                $root = Config::get('app.project_root', getcwd());
                $dbConfig['database'] = $root . '/' . ltrim($dbConfig['database'], './');
            }

            $capsule->addConnection($dbConfig);
            $capsule->setEventDispatcher(new Dispatcher(new Container));
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
        }
    }

    private function ensureTrackingTable(): void
    {
        $schema = DB::connection()->getSchemaBuilder();
        if (!$schema->hasTable('migrations')) {
            $schema->create('migrations', function ($table) {
                $table->id();
                $table->string('migration', 255);
                $table->integer('batch');
                $table->timestamp('executed_at')->useCurrent();
            });
        }
    }

    private function getMigrationFiles(): array
    {
        if (!is_dir($this->path)) {
            return [];
        }
        $files = glob($this->path . '*.php');
        if ($files === false) {
            return [];
        }
        sort($files);
        return $files;
    }

    private function getExecutedMigrations(): array
    {
        try {
            return DB::table('migrations')->pluck('migration')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function loadMigration(string $file): Migration
    {
        $migration = require $file;
        if (!$migration instanceof Migration) {
            throw new \RuntimeException("Migration file $file must return an instance of " . Migration::class);
        }
        return $migration;
    }

    private function getNextBatch(): int
    {
        try {
            $max = DB::table('migrations')->max('batch');
            return ($max ?? 0) + 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

    private function resetTrackingTable(): void
    {
        $schema = DB::connection()->getSchemaBuilder();
        $schema->drop('migrations');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit test/Database/Migrations/MigrationRunnerTest.php`
Expected: PASS — 7 tests

- [ ] **Step 5: Commit**

```bash
git add src/Simple/Database/Migrations/MigrationRunner.php test/Database/Migrations/MigrationRunnerTest.php
git commit -m "feat: add MigrationRunner with run/fresh support"
```

---

### Task 4: MakeMigrationCommand + Test

**Files:**
- Create: `src/Simple/Engine/Commands/MakeMigrationCommand.php`
- Create: `test/Engine/Commands/MakeMigrationCommandTest.php`

- [ ] **Step 1: Write the failing test for MakeMigrationCommand**

```php
<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\MakeMigrationCommand;

class MakeMigrationCommandTest extends TestCase
{
    private string $migrationsDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->migrationsDir = sys_get_temp_dir() . '/make-migration-test-' . getmypid();
        mkdir($this->migrationsDir, 0777, true);
        $this->originalCwd = getcwd();
        chdir($this->migrationsDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        array_map('unlink', glob($this->migrationsDir . '/database/migrations/*.php'));
        $this->rrmdir($this->migrationsDir);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testCreatesMigrationFile(): void
    {
        $command = new MakeMigrationCommand();
        $result = $command->handle(['CreateUsersTable']);

        $this->assertSame('success', $result['type']);
        $files = glob($this->migrationsDir . '/database/migrations/*.php');
        $this->assertCount(1, $files);
    }

    public function testCreatedFileHasTimestampPrefix(): void
    {
        $command = new MakeMigrationCommand();
        $command->handle(['CreateUsersTable']);

        $files = glob($this->migrationsDir . '/database/migrations/*.php');
        $filename = basename($files[0]);
        $this->assertMatchesRegularExpression('/^\d{4}_\d{2}_\d{2}_\d{6}_CreateUsersTable\.php$/', $filename);
    }

    public function testCreatedFileContainsSchemaCreate(): void
    {
        $command = new MakeMigrationCommand();
        $command->handle(['CreateUsersTable']);

        $files = glob($this->migrationsDir . '/database/migrations/*.php');
        $contents = file_get_contents($files[0]);
        $this->assertStringContainsString("Schema::create('users'", $contents);
    }

    public function testCreatedFileContainsDropIfExists(): void
    {
        $command = new MakeMigrationCommand();
        $command->handle(['CreateUsersTable']);

        $files = glob($this->migrationsDir . '/database/migrations/*.php');
        $contents = file_get_contents($files[0]);
        $this->assertStringContainsString("Schema::dropIfExists('users'", $contents);
    }

    public function testReturnsErrorWhenNameMissing(): void
    {
        $command = new MakeMigrationCommand();
        $result = $command->handle([]);

        $this->assertSame('error', $result['type']);
    }

    public function testCreatesMigrationsDirectoryIfMissing(): void
    {
        $command = new MakeMigrationCommand();
        $result = $command->handle(['CreatePostsTable']);

        $this->assertSame('success', $result['type']);
        $this->assertFileExists($this->migrationsDir . '/database/migrations');
    }

    public function testMigrationNameWithUnderscores(): void
    {
        $command = new MakeMigrationCommand();
        $command->handle(['add_status_to_users']);

        $files = glob($this->migrationsDir . '/database/migrations/*.php');
        $filename = basename($files[0]);
        $this->assertStringContainsString('add_status_to_users', $filename);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit test/Engine/Commands/MakeMigrationCommandTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create MakeMigrationCommand**

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeMigrationCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $name = $args[0] ?? null;
        if (!$name) {
            return ['type' => 'error', 'message' => 'Migration name must be defined'];
        }

        $timestamp = date('Y_m_d_His');
        $filename = $timestamp . '_' . $name . '.php';
        $dir = './database/migrations/';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tableName = $this->deriveTableName($name);

        $stub = file_get_contents(__DIR__ . '/../Stubs/migration.stub');
        $stub = str_replace('{{tableName}}', $tableName, $stub);

        $filePath = $dir . $filename;
        file_put_contents($filePath, $stub);

        return ['type' => 'success', 'message' => "Migration $filename created successfully"];
    }

    private function deriveTableName(string $name): string
    {
        $name = preg_replace('/^create_/i', '', $name);
        $name = preg_replace('/_table$/i', '', $name);
        $name = preg_replace('/^add_.*_to_/i', '', $name);
        $name = preg_replace('/^drop_.*_from_/i', '', $name);
        $name = str_replace('_', ' ', $name);
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        $name = strtolower($name);
        $name = str_replace(' ', '_', trim($name));
        return $name ?: 'table';
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit test/Engine/Commands/MakeMigrationCommandTest.php`
Expected: PASS — 7 tests

- [ ] **Step 5: Commit**

```bash
git add src/Simple/Engine/Commands/MakeMigrationCommand.php test/Engine/Commands/MakeMigrationCommandTest.php
git commit -m "feat: add make:migration command with stub-based generation"
```

---

### Task 5: Rewrite MigrateCommand + Test

**Files:**
- Modify: `src/Simple/Engine/Commands/MigrateCommand.php` (complete rewrite)
- Create: `test/Engine/Commands/MigrateCommandTest.php`

- [ ] **Step 1: Write the failing test for MigrateCommand**

```php
<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\MigrateCommand;

class MigrateCommandTest extends TestCase
{
    private string $migrationsDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->migrationsDir = sys_get_temp_dir() . '/migrate-cmd-test-' . getmypid();
        mkdir($this->migrationsDir . '/database/migrations', 0777, true);
        $this->originalCwd = getcwd();
        chdir($this->migrationsDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        $this->rrmdir($this->migrationsDir);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testMigrateWithNoMigrationsReturnsSuccess(): void
    {
        $command = new MigrateCommand();
        $result = $command->handle([]);

        $this->assertSame('success', $result['type']);
    }

    public function testMigrateRunsPendingMigration(): void
    {
        file_put_contents($this->migrationsDir . '/database/migrations/2026_01_01_000001_test.php', '<?php
            use Simple\Database\Migrations\Migration;
            use Illuminate\Support\Facades\Schema;
            use Illuminate\Database\Schema\Blueprint;
            return new class extends Migration {
                public function up(): void { Schema::create("test_table", function (Blueprint $t) { $t->id(); }); }
                public function down(): void { Schema::dropIfExists("test_table"); }
            };
        ');

        \Simple\Config::set('database.engine', 'sqlite');
        \Simple\Config::set('database.name', ':memory:');

        $command = new MigrateCommand();
        ob_start();
        $result = $command->handle([]);
        ob_get_clean();

        $this->assertSame('success', $result['type']);
    }

    public function testMigrateWithExistingMigrationsDoesNotRerun(): void
    {
        file_put_contents($this->migrationsDir . '/database/migrations/2026_01_01_000001_test.php', '<?php
            use Simple\Database\Migrations\Migration;
            use Illuminate\Support\Facades\Schema;
            use Illuminate\Database\Schema\Blueprint;
            return new class extends Migration {
                public function up(): void { Schema::create("test_table", function (Blueprint $t) { $t->id(); }); }
                public function down(): void { Schema::dropIfExists("test_table"); }
            };
        ');

        \Simple\Config::set('database.engine', 'sqlite');
        \Simple\Config::set('database.name', ':memory:');

        $command = new MigrateCommand();
        ob_start();
        $command->handle([]);
        ob_get_clean();

        ob_start();
        $result = $command->handle([]);
        ob_get_clean();

        $this->assertSame('success', $result['type']);
    }

    public function testMigrateReturnsErrorOnBadMigration(): void
    {
        file_put_contents($this->migrationsDir . '/database/migrations/2026_01_01_000001_bad.php', '<?php return "bad"; ');

        \Simple\Config::set('database.engine', 'sqlite');
        \Simple\Config::set('database.name', ':memory:');

        $command = new MigrateCommand();
        ob_start();
        $result = $command->handle([]);
        ob_get_clean();

        $this->assertSame('error', $result['type']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit test/Engine/Commands/MigrateCommandTest.php`
Expected: FAIL — the old MigrateCommand still handles raw SQL

- [ ] **Step 3: MigrateCommand.php (rewrite)**

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;
use Simple\Database\Migrations\MigrationRunner;

class MigrateCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $runner = new MigrationRunner();
        try {
            $ran = $runner->run();
        } catch (\RuntimeException $e) {
            return ['type' => 'error', 'message' => $e->getMessage()];
        }

        if (empty($ran)) {
            return ['type' => 'success', 'message' => 'Nothing to migrate.'];
        }

        echo PHP_EOL . 'Migrated:' . PHP_EOL;
        foreach ($ran as $file) {
            echo "  - $file" . PHP_EOL;
        }
        return ['type' => 'success', 'message' => count($ran) . ' migration(s) ran successfully.'];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit test/Engine/Commands/MigrateCommandTest.php`
Expected: PASS — 4 tests

- [ ] **Step 5: Commit**

```bash
git add src/Simple/Engine/Commands/MigrateCommand.php test/Engine/Commands/MigrateCommandTest.php
git commit -m "feat: rewrite migrate command to use MigrationRunner"
```

---

### Task 6: MigrateFreshCommand + Test

**Files:**
- Create: `src/Simple/Engine/Commands/MigrateFreshCommand.php`
- Create: `test/Engine/Commands/MigrateFreshCommandTest.php`

- [ ] **Step 1: Write the failing test for MigrateFreshCommand**

```php
<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\MigrateFreshCommand;

class MigrateFreshCommandTest extends TestCase
{
    private string $migrationsDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->migrationsDir = sys_get_temp_dir() . '/migrate-fresh-test-' . getmypid();
        mkdir($this->migrationsDir . '/database/migrations', 0777, true);
        $this->originalCwd = getcwd();
        chdir($this->migrationsDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        $this->rrmdir($this->migrationsDir);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testFreshWithNoMigrationsReturnsSuccess(): void
    {
        \Simple\Config::set('database.engine', 'sqlite');
        \Simple\Config::set('database.name', ':memory:');

        $command = new MigrateFreshCommand();
        ob_start();
        $result = $command->handle([]);
        ob_get_clean();

        $this->assertSame('success', $result['type']);
    }

    public function testFreshDropsTablesAndReRuns(): void
    {
        file_put_contents($this->migrationsDir . '/database/migrations/2026_01_01_000001_test.php', '<?php
            use Simple\Database\Migrations\Migration;
            use Illuminate\Support\Facades\Schema;
            use Illuminate\Database\Schema\Blueprint;
            return new class extends Migration {
                public function up(): void { Schema::create("users", function (Blueprint $t) { $t->id(); }); }
                public function down(): void { Schema::dropIfExists("users"); }
            };
        ');

        \Simple\Config::set('database.engine', 'sqlite');
        \Simple\Config::set('database.name', ':memory:');

        $command = new MigrateFreshCommand();
        ob_start();
        $result = $command->handle([]);
        ob_get_clean();

        $this->assertSame('success', $result['type']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit test/Engine/Commands/MigrateFreshCommandTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Create MigrateFreshCommand**

```php
<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;
use Simple\Database\Migrations\MigrationRunner;

class MigrateFreshCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $runner = new MigrationRunner();
        try {
            $ran = $runner->fresh();
        } catch (\RuntimeException $e) {
            return ['type' => 'error', 'message' => $e->getMessage()];
        }

        if (empty($ran)) {
            return ['type' => 'success', 'message' => 'No migrations found to run.'];
        }

        echo PHP_EOL . 'Migrated (fresh):' . PHP_EOL;
        foreach ($ran as $file) {
            echo "  - $file" . PHP_EOL;
        }
        return ['type' => 'success', 'message' => count($ran) . ' migration(s) ran.'];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit test/Engine/Commands/MigrateFreshCommandTest.php`
Expected: PASS — 2 tests

- [ ] **Step 5: Commit**

```bash
git add src/Simple/Engine/Commands/MigrateFreshCommand.php test/Engine/Commands/MigrateFreshCommandTest.php
git commit -m "feat: add migrate:fresh command"
```

---

### Task 7: Register Commands in CommandRegistry

**Files:**
- Modify: `src/Simple/Engine/CommandRegistry.php`

- [ ] **Step 1: Add new commands to the registry**

Add entries for `make:migration`, `migrate` (already exists, class changes), and `migrate:fresh`:

```php
'make:migration'   => Commands\MakeMigrationCommand::class,
'migrate'          => Commands\MigrateCommand::class,
'migrate:fresh'    => Commands\MigrateFreshCommand::class,
```

The final registry should look like:

```php
private static array $commands = [
    'make:controller' => Commands\MakeControllerCommand::class,
    'make:model'      => Commands\MakeModelCommand::class,
    'make:observer'   => Commands\MakeObserverCommand::class,
    'make:request'    => Commands\MakeRequestCommand::class,
    'make:auth'       => Commands\MakeAuthCommand::class,
    'make:migration'  => Commands\MakeMigrationCommand::class,
    'migrate'         => Commands\MigrateCommand::class,
    'migrate:fresh'   => Commands\MigrateFreshCommand::class,
    'user:seed'       => Commands\SeedCommand::class,
    'session:destroy' => Commands\SessionDestroyCommand::class,
    'cache:clear'     => Commands\CacheClearCommand::class,
    'serve'           => Commands\ServeCommand::class,
    'key:generate'    => Commands\KeyGenerateCommand::class,
    'route:list'      => Commands\RouteListCommand::class,
    'help'            => Commands\HelpCommand::class,
    '-help'           => Commands\HelpCommand::class,
];
```

- [ ] **Step 2: Run CommandRegistry tests to confirm**

Run: `vendor/bin/phpunit test/Engine/CommandRegistryTest.php`
Expected: PASS — update the `testAllReturnsSixteenCommands` assertion count to 16

Update the assertion:

```php
public function testAllReturnsSixteenCommands(): void
{
    $this->assertCount(16, CommandRegistry::all());
}
```

- [ ] **Step 3: Run all tests to confirm nothing broken**

Run: `vendor/bin/phpunit test/`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add src/Simple/Engine/CommandRegistry.php test/Engine/CommandRegistryTest.php
git commit -m "feat: register make:migration, migrate, migrate:fresh in CommandRegistry"
```

---

### Task 8: Update Documentation in simply-docs

**Files:**
- Modify: `simply-docs/app/Views/cli.view.html` (CLI commands reference)
- Modify: `simply-docs/app/Views/database.html` (database docs, if exists)

- [ ] **Step 1: Update CLI docs**

Replace the old `migrate` command description with the new migration commands. The old docs reference raw SQL file importing, MySQL-only caveats, and the `-c` flag — all replaced.

Add entries for:
- `make:migration MigrationName` — creates a new migration file in `database/migrations/`
- `migrate` — runs all pending class-based migrations
- `migrate:fresh` — drops all tables and re-runs every migration

- [ ] **Step 2: Add migration section to database docs**

Cover:
- Creating a migration with `make:migration`
- Writing `up()` and `down()` with Schema builder
- Running migrations with `migrate`
- Resetting with `migrate:fresh`
- Migration file naming convention and directory

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "docs: update migration documentation for class-based migrations"
```

---

### Task 9: Verify

**Files:** None (verification only)

- [ ] **Step 1: Run full test suite**

```bash
vendor/bin/phpunit
```

Expected: All tests pass (existing + new)

- [ ] **Step 2: Run HelpCommandTest to confirm new commands appear in help**

The help text doesn't auto-generate from the registry — it's hardcoded in HelpCommand. Verify the new commands (`make:migration`, `migrate:fresh`) are listed in the help output. If they're missing, they should be added to HelpCommand.

Add to `HelpCommand.php`:

```php
echo $output->print_o(" make:migration MigrationName", 'green', 'black') . " Creates a new migration file" . PHP_EOL;
echo $output->print_o(" migrate", 'green', 'black') . " Runs all pending migrations" . PHP_EOL;
echo $output->print_o(" migrate:fresh", 'green', 'black') . " Drops all tables and re-runs all migrations" . PHP_EOL;
```

- [ ] **Step 3: Commit any final fixes**

```bash
git add -A
git commit -m "chore: final verification and cleanup"
```
