<?php

namespace Simple\Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class MigrationRunner
{
    protected string $path;

    protected string $table = 'migrations';

    public function __construct(string $path = 'database/migrations')
    {
        $this->path = $path;
    }

    public function run(): int
    {
        $this->ensureTrackingTable();

        if (!$this->migrationDirExists()) {
            return 0;
        }

        $files = $this->getMigrationFiles();
        $ran = $this->getRan();

        $pending = array_diff($files, $ran);
        sort($pending);

        $count = 0;
        foreach ($pending as $file) {
            $this->runFile($file);
            $this->log($file);
            $count++;
        }

        return $count;
    }

    public function fresh(): int
    {
        $this->ensureTrackingTable();
        $this->dropAllUserTables();

        Capsule::connection()->table($this->table)->truncate();

        echo "Dropped all tables successfully.\n";

        return $this->run();
    }

    public function ensureTrackingTable(): void
    {
        $schema = Capsule::connection()->getSchemaBuilder();

        if (!$schema->hasTable($this->table)) {
            $schema->create($this->table, function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
                $table->timestamps();
            });
        }
    }

    public function getRan(): array
    {
        return Capsule::connection()
            ->table($this->table)
            ->orderBy('migration')
            ->pluck('migration')
            ->all();
    }

    public function getRanWithBatch(): array
    {
        $rows = Capsule::connection()
            ->table($this->table)
            ->orderBy('migration')
            ->get(['migration', 'batch']);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->migration] = $row->batch;
        }
        return $map;
    }

    public function getMigrationFiles(): array
    {
        $files = scandir($this->path);
        $migrations = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $this->path . '/' . $file;
            if (!is_dir($path) && str_ends_with($file, '.php')) {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    protected function runFile(string $file): void
    {
        $migration = require $this->path . '/' . $file;

        if (!$migration instanceof Migration) {
            throw new \RuntimeException("Migration file [$file] must return an instance of " . Migration::class);
        }

        $migration->up();

        echo "Migrated: $file\n";
    }

    protected function log(string $file): void
    {
        $batch = Capsule::connection()
            ->table($this->table)
            ->max('batch') ?? 0;

        Capsule::connection()->table($this->table)->insert([
            'migration' => $file,
            'batch' => $batch + 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function migrationDirExists(): bool
    {
        return is_dir($this->path);
    }

    protected function dropAllUserTables(): void
    {
        $connection = Capsule::connection();
        $driver = $connection->getDriverName();
        $schema = $connection->getSchemaBuilder();

        $tables = $driver === 'sqlite'
            ? $connection->select("SELECT name FROM sqlite_master WHERE type='table'")
            : $connection->select('SHOW TABLES');

        $schema->disableForeignKeyConstraints();

        foreach ($tables as $row) {
            $table = $driver === 'sqlite' ? $row->name : current((array) $row);
            if ($table === $this->table || str_starts_with($table, 'sqlite_')) {
                continue;
            }
            $schema->drop($table);
        }

        $schema->enableForeignKeyConstraints();
    }
}
