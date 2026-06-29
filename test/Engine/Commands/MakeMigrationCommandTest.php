<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\MakeMigrationCommand;

class MakeMigrationCommandTest extends TestCase
{
    private string $tempDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/make-migration-test-' . getmypid();
        mkdir($this->tempDir . '/database/migrations', 0777, true);
        $this->originalCwd = getcwd();
        chdir($this->tempDir);
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

    public function testCreatesMigrationFile(): void
    {
        $command = new MakeMigrationCommand();
        $result = $command->handle(['create_users_table']);

        $this->assertSame('success', $result['type']);
        $files = scandir($this->tempDir . '/database/migrations');
        $phpFiles = array_values(array_filter($files, fn($f) => str_ends_with($f, '.php')));
        $this->assertCount(1, $phpFiles);
        $this->assertStringContainsString('create_users_table', $phpFiles[0]);
    }

    public function testCreatedMigrationExtendsMigration(): void
    {
        $command = new MakeMigrationCommand();
        $command->handle(['create_posts_table']);

        $files = scandir($this->tempDir . '/database/migrations');
        $phpFiles = array_values(array_filter($files, fn($f) => str_ends_with($f, '.php')));
        $contents = file_get_contents($this->tempDir . '/database/migrations/' . $phpFiles[0]);
        $this->assertStringContainsString('extends Migration', $contents);
        $this->assertStringContainsString('use Simple\Database\Migrations\Migration;', $contents);
    }

    public function testCreatedMigrationHasTableNameInSchema(): void
    {
        $command = new MakeMigrationCommand();
        $command->handle(['create_users_table']);

        $files = scandir($this->tempDir . '/database/migrations');
        $phpFiles = array_values(array_filter($files, fn($f) => str_ends_with($f, '.php')));
        $contents = file_get_contents($this->tempDir . '/database/migrations/' . $phpFiles[0]);
        $this->assertStringContainsString("'users'", $contents);
    }

    public function testFilenameHasTimestampPrefix(): void
    {
        $command = new MakeMigrationCommand();
        $command->handle(['create_products_table']);

        $files = scandir($this->tempDir . '/database/migrations');
        $phpFiles = array_values(array_filter($files, fn($f) => str_ends_with($f, '.php')));
        $this->assertMatchesRegularExpression('/^\d{4}_\d{2}_\d{2}_\d{6}_/', $phpFiles[0]);
    }

    public function testReturnsErrorWhenNameMissing(): void
    {
        $command = new MakeMigrationCommand();
        $result = $command->handle([]);

        $this->assertSame('error', $result['type']);
        $this->assertStringContainsString('name must be defined', $result['message']);
    }

    public function testCreatesMigrationDirectoryIfMissing(): void
    {
        $this->rrmdir($this->tempDir . '/database/migrations');
        $command = new MakeMigrationCommand();
        $result = $command->handle(['create_roles_table']);

        $this->assertSame('success', $result['type']);
        $this->assertDirectoryExists($this->tempDir . '/database/migrations');
    }

    public function testInferTableNameWithoutCreatePrefix(): void
    {
        $command = new MakeMigrationCommand();
        $command->handle(['create_orders_table']);

        $files = scandir($this->tempDir . '/database/migrations');
        $phpFiles = array_values(array_filter($files, fn($f) => str_ends_with($f, '.php')));
        $contents = file_get_contents($this->tempDir . '/database/migrations/' . $phpFiles[0]);
        $this->assertStringContainsString("'orders'", $contents);
    }

    public function testInferTableNameWithoutSuffix(): void
    {
        $command = new MakeMigrationCommand();
        $command->handle(['add_email_to_users']);

        $files = scandir($this->tempDir . '/database/migrations');
        $phpFiles = array_values(array_filter($files, fn($f) => str_ends_with($f, '.php')));
        $contents = file_get_contents($this->tempDir . '/database/migrations/' . $phpFiles[0]);
        $this->assertStringContainsString("'email_to_users'", $contents);
    }
}
