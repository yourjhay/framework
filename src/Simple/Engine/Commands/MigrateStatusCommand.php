<?php

namespace Simple\Engine\Commands;

use Simple\Database\Connection;
use Simple\Database\Migrations\MigrationRunner;
use Simple\Engine\Contracts\CommandInterface;
use LucidFrame\Console\ConsoleTable;

class MigrateStatusCommand implements CommandInterface
{
    use Connection;

    public function handle(array $args): ?array
    {
        $this->connect();

        $path = $args[0] ?? 'database/migrations';
        $runner = new MigrationRunner($path);
        $runner->ensureTrackingTable();

        $files = $runner->getMigrationFiles();
        $ranMap = $runner->getRanWithBatch();

        if (empty($files)) {
            return ['type' => 'success', 'message' => 'No migration files found in ' . $path];
        }

        $table = new ConsoleTable();
        $table->setHeaders(['Migration', 'Status', 'Batch']);

        foreach ($files as $file) {
            if (isset($ranMap[$file])) {
                $table->addRow([$file, 'Ran', (string) $ranMap[$file]]);
            } else {
                $table->addRow([$file, 'Pending', '']);
            }
        }

        $table->display();

        return null;
    }
}
