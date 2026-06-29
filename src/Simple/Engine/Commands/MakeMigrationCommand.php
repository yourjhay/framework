<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeMigrationCommand implements CommandInterface
{
    private string $migrationPath = 'database/migrations/';

    public function handle(array $args): ?array
    {
        $name = $args[0] ?? null;
        if (!$name) {
            return ['type' => 'error', 'message' => 'Migration name must be defined'];
        }

        $stub = file_get_contents(__DIR__ . '/../Stubs/migration.stub');
        $tableName = $this->inferTableName($name);
        $stub = str_replace('{{tableName}}', $tableName, $stub);

        $timestamp = date('Y_m_d_His');
        $filename = $timestamp . '_' . $name . '.php';

        $dir = $this->migrationPath;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . $filename;
        if (file_exists($path)) {
            return ['type' => 'error', 'message' => "Migration [$filename] already exists!"];
        }

        file_put_contents($path, $stub);
        return ['type' => 'success', 'message' => "Migration [$filename] created successfully"];
    }

    private function inferTableName(string $name): string
    {
        $name = strtolower($name);

        $prefixes = ['create_', 'add_', 'drop_', 'alter_', 'modify_', 'rename_'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                $name = substr($name, strlen($prefix));
                break;
            }
        }

        $suffixes = ['_table', '_to_users_table', '_to_roles_table'];
        foreach ($suffixes as $suffix) {
            if (str_ends_with($name, $suffix)) {
                $name = substr($name, 0, -strlen($suffix));
                break;
            }
        }

        return $name;
    }
}
