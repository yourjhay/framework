# Database Migrations — Simply PHP

## Overview

Add class-based database migrations to Simply PHP with a custom lightweight runner, replacing the current raw SQL file piping. Uses Eloquent Schema builder (already available via `illuminate/database`) for portable, type-checked table definitions.

## Requirements

- PHP `^8.0` (matches framework requirement, no bump needed)
- No new runtime dependencies — `illuminate/database` already provides Schema/Blueprint
- Support both MySQL and SQLite (current engines)
- `illuminate/filesystem` explicitly **not** required — custom runner handles file scanning

## User API

### Migration file (`database/migrations/`)

```php
<?php

use Simple\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

- Anonymous class — no naming collisions, no autoloading needed
- `Migration` base class enforces `up()`/`down()` contract
- `Schema` facade aliased from `Illuminate\Support\Facades\Schema`

### CLI commands

| Command | Behaviour |
|---------|-----------|
| `php cli make:migration CreateUsersTable` | Generates timestamped file in `database/migrations/` |
| `php cli migrate` | Runs all pending migrations (sorted by filename) |
| `php cli migrate:fresh` | Drops all user tables, re-runs every migration |

### Migration stub (`make:migration` template)

```php
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

## Architecture

### File layout

```
framework/src/Simple/Database/
├── Migrations/
│   ├── Migration.php              # Base class
│   └── MigrationRunner.php        # Core runner
├── Stubs/
│   └── migration.stub             # Template for make:migration

framework/src/Simple/Engine/Commands/
├── MakeMigrationCommand.php       # make:migration
├── MigrateCommand.php             # migrate (replaces existing MigrateCommand)
└── MigrateFreshCommand.php        # migrate:fresh
```

### Migration base class

```php
namespace Simple\Database\Migrations;

abstract class Migration
{
    abstract public function up(): void;
    abstract public function down(): void;
}
```

Minimal — just enforces the contract. No Illuminate `Migration` dependency.

### MigrationRunner

```php
class MigrationRunner
{
    private string $path = './database/migrations/';
    private ?string $connection = null;       // optional DB connection name
    private string $table = 'migrations';     // tracking table name

    public function run(): array;             // returns list of ran filenames
    public function fresh(): array;           // drops tables, then run()
}
```

**Run flow:**
1. Ensure tracking table exists (`CREATE TABLE IF NOT EXISTS migrations`)
2. Scan `$path` for `.php` files, sorted ascending
3. Filter against already-tracked migrations
4. For each: require file, get returned anonymous class, verify it extends `Migration`
5. Execute within a transaction: call `up()`, then `INSERT INTO migrations`
6. Return list of ran filenames

**Fresh flow:**
1. Discover all non-migration tables via `SHOW TABLES` (MySQL) or `sqlite_master` (SQLite)
2. Disable foreign key checks, drop them
3. Re-run full migration batch (same as `run()`)

### Tracking table

```sql
CREATE TABLE IF NOT EXISTS migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

- `migration` — filename (e.g. `2026_06_29_000001_create_users_table.php`)
- `batch` — incremented per `run()` invocation
- `executed_at` — when the migration ran

### Wire-up (CLI commands)

`MakeMigrationCommand` — generates file from stub:

```php
$name = $args[0] ?? null;                         // 'CreateUsersTable'
$timestamp = date('Y_m_d_His');
$filename = "{$timestamp}_{$name}.php";
$tableName = Str::snake(str_replace('Create', '', str_replace('Table', '', $name)));
// Fill stub, write to database/migrations/
```

`MigrateCommand` — creates runner, calls `run()`:

```php
$runner = new MigrationRunner();
$ran = $runner->run();
// output each ran migration
```

`MigrateFreshCommand` — calls `fresh()` on runner.

### Schema builder access

Migrations use `Illuminate\Support\Facades\Schema` (Schema facade). This works after `Capsule::bootEloquent()` has been called — which the framework already does via the `Connection` trait and `DB` class. The MigrationRunner bootstraps the Capsule if not already connected, making Schema available.

Alternative inline approach (no facade needed):

```php
$schema = DB::connection()->getSchemaBuilder();
$schema->create('users', function (Blueprint $table) { ... });
```

The spec recommends using the Schema facade for readability (matches Laravel conventions), but the runner ensures the Capsule is connected before executing migrations.

## Error Handling

- **Tracking table creation failure** → cannot proceed, error message
- **Migration file not found** → empty directory, no-op
- **File doesn't return a Migration instance** → skip with warning
- **`up()` throws** → caught per-migration, transaction rolled back, process stops with error
- **`fresh()` with no tables** → no-op, clean exit
- **Missing `down()`** → skipped during `fresh()` (Schema::drop handles it)

## Out of Scope (v1)

- `migrate:rollback` — defer to v2
- `migrate:reset` — defer to v2
- `migrate:refresh` — defer to v2
- Multi-connection support — defer to v2
- Seeder classes — defer to v2 (existing `user:seed` remains)
- Doctrine DBAL for column type changes — not needed without rollbacks

## Documentation Updates

The following docs in `simply-docs/` need updating:

- **CLI reference** — replace `migrate` description (raw SQL file piping) with new class-based migration commands (`make:migration`, `migrate`, `migrate:fresh`)
- **Database page** — add migration section covering: creating migrations, writing up/down with Schema builder, running/freshing migrations
- **Remove deprecated notes** — the old "NOTE: This only works if DBENGINE=mysql or mysqli" and "import one by one" caveats no longer apply

Documentation changes are tracked as the final task in the implementation plan.

## Testing

- **MigrationRunnerTest** — test against SQLite in-memory database
  - `run()` creates tracking table, executes pending migrations
  - `fresh()` drops tables and re-runs
  - Multiple batches increment correctly
  - Already-run migrations are skipped
- **MakeMigrationCommandTest** — verify file creation in temp dir, naming convention
- **MigrateCommandTest** — integration test via Console dispatch
