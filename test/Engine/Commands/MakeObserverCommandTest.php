<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\MakeObserverCommand;

class MakeObserverCommandTest extends TestCase
{
    private string $tempDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/make-observer-test-' . getmypid();
        mkdir($this->tempDir . '/app/Observers', 0777, true);
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

    public function testCreatesObserverFile(): void
    {
        $command = new MakeObserverCommand();
        $result = $command->handle(['User']);

        $this->assertSame('success', $result['type']);
        $this->assertFileExists($this->tempDir . '/app/Observers/UserObserver.php');
    }

    public function testCreatedObserverContainsNamespace(): void
    {
        $command = new MakeObserverCommand();
        $command->handle(['Order']);

        $contents = file_get_contents($this->tempDir . '/app/Observers/OrderObserver.php');
        $this->assertStringContainsString('namespace App\Observers;', $contents);
    }

    public function testCreatedObserverSuffixesWithObserver(): void
    {
        $command = new MakeObserverCommand();
        $command->handle(['Payment']);

        $this->assertFileExists($this->tempDir . '/app/Observers/PaymentObserver.php');
    }

    public function testObserverNameConvertedToStudlyCaps(): void
    {
        $command = new MakeObserverCommand();
        $command->handle(['order-item']);

        $this->assertFileExists($this->tempDir . '/app/Observers/OrderItemObserver.php');
    }

    public function testReturnsErrorWhenNameMissing(): void
    {
        $command = new MakeObserverCommand();
        $result = $command->handle([]);

        $this->assertSame('error', $result['type']);
    }

    public function testReturnsErrorWhenObserverAlreadyExists(): void
    {
        file_put_contents($this->tempDir . '/app/Observers/UserObserver.php', 'existing');
        $command = new MakeObserverCommand();
        $result = $command->handle(['User']);

        $this->assertSame('error', $result['type']);
    }

    public function testCreatesObserverDirectoryIfMissing(): void
    {
        $this->rrmdir($this->tempDir . '/app/Observers');
        $command = new MakeObserverCommand();
        $result = $command->handle(['User']);

        $this->assertSame('success', $result['type']);
        $this->assertFileExists($this->tempDir . '/app/Observers/UserObserver.php');
    }
}
