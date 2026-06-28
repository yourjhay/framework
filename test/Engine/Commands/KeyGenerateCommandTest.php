<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\KeyGenerateCommand;

class KeyGenerateCommandTest extends TestCase
{
    private string $tempDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/keygen-test-' . getmypid();
        mkdir($this->tempDir, 0777, true);
        $this->originalCwd = getcwd();
        chdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        array_map('unlink', glob($this->tempDir . '/.env*'));
        rmdir($this->tempDir);
    }

    public function testGeneratesKeyInEmptyEnv(): void
    {
        file_put_contents($this->tempDir . '/.env', 'APP_ENV=test' . PHP_EOL);
        $command = new KeyGenerateCommand();
        $result = $command->handle([]);

        $this->assertSame('success', $result['type']);
        $contents = file_get_contents($this->tempDir . '/.env');
        $this->assertStringContainsString('APP_KEY=', $contents);
        $this->assertStringContainsString('APP_ENV=test', $contents);
    }

    public function testReplacesExistingAppKey(): void
    {
        file_put_contents($this->tempDir . '/.env', 'APP_KEY=old_key' . PHP_EOL . 'APP_ENV=test' . PHP_EOL);
        $command = new KeyGenerateCommand();
        $result = $command->handle([]);

        $this->assertSame('success', $result['type']);
        $contents = file_get_contents($this->tempDir . '/.env');
        $this->assertStringContainsString('APP_ENV=test', $contents);
        $newKey = trim(str_replace('APP_KEY=', '', preg_grep('/^APP_KEY=/', explode(PHP_EOL, $contents))[0] ?? ''));
        $this->assertNotEquals('old_key', $newKey);
    }

    public function testReturnsErrorWhenEnvMissing(): void
    {
        $command = new KeyGenerateCommand();
        $result = $command->handle([]);

        $this->assertSame('error', $result['type']);
        $this->assertStringContainsString('.env not found', $result['message']);
    }

    public function testGeneratedKeyIsNonEmpty(): void
    {
        file_put_contents($this->tempDir . '/.env', '');
        $command = new KeyGenerateCommand();
        $command->handle([]);

        $contents = file_get_contents($this->tempDir . '/.env');
        $this->assertStringContainsString('APP_KEY=', $contents);
    }

    public function testAppendsKeyWhenNoKeyExists(): void
    {
        file_put_contents($this->tempDir . '/.env', 'DB_HOST=localhost' . PHP_EOL);
        $command = new KeyGenerateCommand();
        $command->handle([]);

        $contents = file_get_contents($this->tempDir . '/.env');
        $this->assertStringContainsString('APP_KEY=', $contents);
        $this->assertStringContainsString('DB_HOST=localhost', $contents);
    }
}
