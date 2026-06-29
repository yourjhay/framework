<?php

namespace Simple\Engine\Commands;

use Simple\Database\Connection;
use Simple\Database\Migrations\MigrationRunner;
use Simple\Engine\Contracts\CommandInterface;

class MigrateFreshCommand implements CommandInterface
{
    use Connection;

    public function handle(array $args): ?array
    {
        $this->connect();

        $path = $args[0] ?? 'database/migrations';
        $runner = new MigrationRunner($path);

        $count = $runner->fresh();

        return [
            'type' => 'success',
            'message' => $count > 0
                ? "Fresh migrated $count migration(s) successfully"
                : 'No migrations found',
        ];
    }
}
