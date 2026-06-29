<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\CacheClearCommand;

class CacheClearCommandTest extends TestCase
{
    private string $tempDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/cache-clear-test-' . getmypid();
        mkdir($this->tempDir . '/storage/framework/cache/views', 0777, true);
        file_put_contents($this->tempDir . '/storage/framework/cache/views/cache1.php', 'cached');
        file_put_contents($this->tempDir . '/storage/framework/cache/views/cache2.twig', 'cached');
        file_put_contents($this->tempDir . '/storage/framework/cache/views/.gitkeep', '');

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

    public function testClearsPhpCacheFiles(): void
    {
        $command = new CacheClearCommand();
        $result = $command->handle([]);

        $this->assertSame('success', $result['type']);
        $this->assertFileDoesNotExist($this->tempDir . '/storage/framework/cache/views/cache1.php');
        $this->assertFileExists($this->tempDir . '/storage/framework/cache/views/.gitkeep');
    }

    public function testReturnsErrorWhenCacheDirMissing(): void
    {
        chdir($this->originalCwd);
        $command = new CacheClearCommand();
        $result = $command->handle([]);

        $this->assertSame('error', $result['type']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testClearsNestedSubdirectories(): void
    {
        mkdir($this->tempDir . '/storage/framework/cache/views/subdir', 0777, true);
        file_put_contents($this->tempDir . '/storage/framework/cache/views/subdir/other.php', 'data');

        $command = new CacheClearCommand();
        $result = $command->handle([]);

        $this->assertSame('success', $result['type']);
        $this->assertFileDoesNotExist($this->tempDir . '/storage/framework/cache/views/subdir/other.php');
        $this->assertFileDoesNotExist($this->tempDir . '/storage/framework/cache/views/subdir');
    }

    public function testEmptyCacheDirDoesNotError(): void
    {
        array_map('unlink', glob($this->tempDir . '/storage/framework/cache/views/*'));

        $command = new CacheClearCommand();
        $result = $command->handle([]);

        $this->assertSame('success', $result['type']);
    }
}
