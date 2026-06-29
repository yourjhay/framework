<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Config;
use Simple\Engine\Commands\MigrateCommand;
use Simple\Engine\Commands\MigrateFreshCommand;

class MigrateCommandTest extends TestCase
{
    private string $tempDir;
    private string $originalCwd;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/migrate-test-' . getmypid();
        $this->dbPath = $this->tempDir . '/database/database.db';
        mkdir($this->tempDir . '/database/migrations', 0777, true);
        $this->originalCwd = getcwd();
        chdir($this->tempDir);

        Config::clear();
        Config::set('database.engine', 'sqlite');
        Config::set('database.name', $this->dbPath);
        Config::set('database.server', 'localhost');
        Config::set('database.user', 'root');
        Config::set('database.pass', '');
        Config::set('app.project_root', $this->tempDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        $this->rrmdir($this->tempDir);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createMigration(string $filename, string $tableName): void
    {
        $code = <<<PHP
<?php

return new class extends \Simple\Database\Migrations\Migration {
    public function up(): void
    {
        \$this->schema()->create('$tableName', function (\$table) {
            \$table->id();
            \$table->string('name');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        \$this->schema()->dropIfExists('$tableName');
    }
};
PHP;
        file_put_contents($this->tempDir . '/database/migrations/' . $filename, $code);
    }

    public function testMigrateRunsPendingMigrations(): void
    {
        $this->createMigration('2024_01_01_000001_create_users_table.php', 'users');

        $command = new MigrateCommand();
        $result = $command->handle(['database/migrations']);

        $this->assertSame('success', $result['type']);
        $this->assertStringContainsString('Migrated 1 migration(s)', $result['message']);
    }

    public function testMigrateReturnsNothingToMigrate(): void
    {
        $command = new MigrateCommand();
        $result = $command->handle(['database/migrations']);

        $this->assertSame('success', $result['type']);
        $this->assertStringContainsString('Nothing to migrate', $result['message']);
    }

    public function testMigrateDoesNotRerunAlreadyRanMigrations(): void
    {
        $this->createMigration('2024_01_01_000001_create_users_table.php', 'users');

        $command = new MigrateCommand();
        $command->handle(['database/migrations']);
        $result = $command->handle(['database/migrations']);

        $this->assertSame('success', $result['type']);
        $this->assertStringContainsString('Nothing to migrate', $result['message']);
    }

    public function testMigrateRunsMultiplePendingMigrationsInOrder(): void
    {
        $this->createMigration('2024_01_01_000001_create_users_table.php', 'users');
        $this->createMigration('2024_01_01_000002_create_posts_table.php', 'posts');

        $command = new MigrateCommand();
        $result = $command->handle(['database/migrations']);

        $this->assertSame('success', $result['type']);
        $this->assertStringContainsString('Migrated 2 migration(s)', $result['message']);
    }

    public function testFreshDropsTablesAndRerunsAllMigrations(): void
    {
        $this->createMigration('2024_01_01_000001_create_users_table.php', 'users');

        $migrate = new MigrateCommand();
        $migrate->handle(['database/migrations']);

        $fresh = new MigrateFreshCommand();
        $result = $fresh->handle(['database/migrations']);

        $this->assertSame('success', $result['type']);
        $this->assertStringContainsString('Fresh migrated 1 migration(s)', $result['message']);
    }

    public function testFreshReturnsNoMigrationsWhenNoFiles(): void
    {
        $fresh = new MigrateFreshCommand();
        $result = $fresh->handle(['database/migrations']);

        $this->assertSame('success', $result['type']);
        $this->assertStringContainsString('No migrations found', $result['message']);
    }

    public function testMigrateCreatesTablesInDatabase(): void
    {
        $this->createMigration('2024_01_01_000001_create_users_table.php', 'users');

        $command = new MigrateCommand();
        $command->handle(['database/migrations']);

        $pdo = new \PDO('sqlite:' . $this->dbPath);
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertContains('users', $tables);
    }
}
