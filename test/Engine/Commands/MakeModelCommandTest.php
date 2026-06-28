<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\MakeModelCommand;

class MakeModelCommandTest extends TestCase
{
    private string $tempDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/make-model-test-' . getmypid();
        mkdir($this->tempDir . '/app/Models', 0777, true);
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

    public function testCreatesModelFile(): void
    {
        $command = new MakeModelCommand();
        $result = $command->handle(['User']);

        $this->assertSame('success', $result['type']);
        $this->assertFileExists($this->tempDir . '/app/Models/User.php');
    }

    public function testCreatedModelContainsNamespace(): void
    {
        $command = new MakeModelCommand();
        $command->handle(['Product']);

        $contents = file_get_contents($this->tempDir . '/app/Models/Product.php');
        $this->assertStringContainsString('namespace App\Models;', $contents);
    }

    public function testCreatedModelExtendsModel(): void
    {
        $command = new MakeModelCommand();
        $command->handle(['Product']);

        $contents = file_get_contents($this->tempDir . '/app/Models/Product.php');
        $this->assertStringContainsString('extends Model', $contents);
    }

    public function testCreatedModelHasTableName(): void
    {
        $command = new MakeModelCommand();
        $command->handle(['Product']);

        $contents = file_get_contents($this->tempDir . '/app/Models/Product.php');
        $this->assertStringContainsString("'products'", $contents);
    }

    public function testModelNameConvertedToStudlyCaps(): void
    {
        $command = new MakeModelCommand();
        $command->handle(['order-item']);

        $this->assertFileExists($this->tempDir . '/app/Models/OrderItem.php');
    }

    public function testModelNameWithHyphenConvertsTableName(): void
    {
        $command = new MakeModelCommand();
        $command->handle(['OrderItem']);

        $contents = file_get_contents($this->tempDir . '/app/Models/OrderItem.php');
        $this->assertStringContainsString("'orderitems'", $contents);
    }

    public function testReturnsErrorWhenNameMissing(): void
    {
        $command = new MakeModelCommand();
        $result = $command->handle([]);

        $this->assertSame('error', $result['type']);
        $this->assertStringContainsString('name must be defined', $result['message']);
    }

    public function testReturnsErrorWhenModelAlreadyExists(): void
    {
        file_put_contents($this->tempDir . '/app/Models/User.php', 'existing');
        $command = new MakeModelCommand();
        $result = $command->handle(['User']);

        $this->assertSame('error', $result['type']);
    }

    public function testCreatesModelDirectoryIfMissing(): void
    {
        $this->rrmdir($this->tempDir . '/app/Models');
        $command = new MakeModelCommand();
        $result = $command->handle(['User']);

        $this->assertSame('success', $result['type']);
        $this->assertFileExists($this->tempDir . '/app/Models/User.php');
    }
}
