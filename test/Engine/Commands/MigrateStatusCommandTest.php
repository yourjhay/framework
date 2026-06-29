<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Config;
use Simple\Engine\Commands\MigrateCommand;
use Simple\Engine\Commands\MigrateStatusCommand;

class MigrateStatusCommandTest extends TestCase
{
    private string $tempDir;
    private string $originalCwd;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/migrate-status-test-' . getmypid();
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

use Simple\Database\Migrations\Migration;

return new class extends Migration {
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

    public function testStatusShowsPendingWhenNoMigrationsRan(): void
    {
        $this->createMigration('2024_01_01_000001_create_users_table.php', 'users');

        ob_start();
        $command = new MigrateStatusCommand();
        $command->handle(['database/migrations']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Pending', $output);
        $this->assertStringContainsString('create_users_table', $output);
    }

    public function testStatusShowsRanAfterMigration(): void
    {
        $this->createMigration('2024_01_01_000001_create_users_table.php', 'users');

        try {
            $migrate = new MigrateCommand();
            $migrate->handle(['database/migrations']);
        } catch (\Exception $e) {
            // ignore output from migrate command
        }

        ob_start();
        $command = new MigrateStatusCommand();
        $command->handle(['database/migrations']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Ran', $output);
        $this->assertStringContainsString('1', $output);
    }

    public function testStatusShowsAllPendingWhenMultipleFiles(): void
    {
        $this->createMigration('2024_01_01_000001_create_users_table.php', 'users');
        $this->createMigration('2024_01_01_000002_create_posts_table.php', 'posts');

        ob_start();
        $command = new MigrateStatusCommand();
        $command->handle(['database/migrations']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Pending', $output);
        $this->assertStringContainsString('create_users_table', $output);
        $this->assertStringContainsString('create_posts_table', $output);
    }

    public function testStatusReturnsMessageWhenNoFiles(): void
    {
        $command = new MigrateStatusCommand();
        $result = $command->handle(['database/migrations']);

        $this->assertSame('success', $result['type']);
        $this->assertStringContainsString('No migration files found', $result['message']);
    }

    public function testStatusTableHeadersPresent(): void
    {
        $this->createMigration('2024_01_01_000001_create_users_table.php', 'users');

        ob_start();
        $command = new MigrateStatusCommand();
        $command->handle(['database/migrations']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Migration', $output);
        $this->assertStringContainsString('Status', $output);
        $this->assertStringContainsString('Batch', $output);
    }
}
