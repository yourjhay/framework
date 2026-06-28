<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\MakeControllerCommand;

class MakeControllerCommandTest extends TestCase
{
    private string $tempDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/make-controller-test-' . getmypid();
        mkdir($this->tempDir . '/app/Controllers', 0777, true);
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

    public function testCreatesBasicController(): void
    {
        $command = new MakeControllerCommand();
        $result = $command->handle(['Page']);

        $this->assertSame('success', $result['type']);
        $this->assertFileExists($this->tempDir . '/app/Controllers/PageController.php');
    }

    public function testBasicControllerHasNamespace(): void
    {
        $command = new MakeControllerCommand();
        $command->handle(['Page']);

        $contents = file_get_contents($this->tempDir . '/app/Controllers/PageController.php');
        $this->assertStringContainsString('namespace App\Controllers;', $contents);
    }

    public function testBasicControllerExtendsController(): void
    {
        $command = new MakeControllerCommand();
        $command->handle(['Page']);

        $contents = file_get_contents($this->tempDir . '/app/Controllers/PageController.php');
        $this->assertStringContainsString('extends Controller', $contents);
    }

    public function testBasicControllerHasIndexMethod(): void
    {
        $command = new MakeControllerCommand();
        $command->handle(['Page']);

        $contents = file_get_contents($this->tempDir . '/app/Controllers/PageController.php');
        $this->assertStringContainsString('public function index', $contents);
    }

    public function testBasicControllerDoesNotHaveCrudMethods(): void
    {
        $command = new MakeControllerCommand();
        $command->handle(['Page']);

        $contents = file_get_contents($this->tempDir . '/app/Controllers/PageController.php');
        $this->assertStringNotContainsString('public function create', $contents);
        $this->assertStringNotContainsString('public function store', $contents);
        $this->assertStringNotContainsString('public function edit', $contents);
        $this->assertStringNotContainsString('public function update', $contents);
        $this->assertStringNotContainsString('public function destroy', $contents);
    }

    public function testAutoAppendsControllerSuffix(): void
    {
        $command = new MakeControllerCommand();
        $command->handle(['User']);

        $this->assertFileExists($this->tempDir . '/app/Controllers/UserController.php');
    }

    public function testDoesNotDuplicateControllerSuffix(): void
    {
        $command = new MakeControllerCommand();
        $command->handle(['UserController']);

        $this->assertFileExists($this->tempDir . '/app/Controllers/UserController.php');
    }

    public function testNameConvertedToStudlyCaps(): void
    {
        $command = new MakeControllerCommand();
        $command->handle(['order-item']);

        $this->assertFileExists($this->tempDir . '/app/Controllers/OrderItemController.php');
    }

    public function testResourceControllerHasCrudMethods(): void
    {
        $command = new MakeControllerCommand();
        $command->handle(['Product', '-r']);

        $contents = file_get_contents($this->tempDir . '/app/Controllers/ProductController.php');
        $this->assertStringContainsString('public function index', $contents);
        $this->assertStringContainsString('public function create', $contents);
        $this->assertStringContainsString('public function store', $contents);
        $this->assertStringContainsString('public function edit', $contents);
        $this->assertStringContainsString('public function update', $contents);
        $this->assertStringContainsString('public function destroy', $contents);
    }

    public function testResourceControllerWithModelFlagCreatesModel(): void
    {
        $command = new MakeControllerCommand();
        $command->handle(['Product', '-rm']);

        $this->assertFileExists($this->tempDir . '/app/Controllers/ProductController.php');
        $this->assertFileExists($this->tempDir . '/app/Models/Product.php');
    }

    public function testModelFlagCreatesModel(): void
    {
        $command = new MakeControllerCommand();
        $command->handle(['Product', '-m']);

        $this->assertFileExists($this->tempDir . '/app/Controllers/ProductController.php');
        $this->assertFileExists($this->tempDir . '/app/Models/Product.php');
    }

    public function testResourceControllerWithModelDoesNotDuplicateModel(): void
    {
        file_put_contents($this->tempDir . '/app/Models/Product.php', 'existing');
        $command = new MakeControllerCommand();
        $command->handle(['Product', '-rm']);

        $contents = file_get_contents($this->tempDir . '/app/Models/Product.php');
        $this->assertSame('existing', $contents);
    }

    public function testReturnsErrorWhenNameMissing(): void
    {
        $command = new MakeControllerCommand();
        $result = $command->handle([]);

        $this->assertSame('error', $result['type']);
    }

    public function testReturnsErrorWhenControllerAlreadyExists(): void
    {
        file_put_contents($this->tempDir . '/app/Controllers/PageController.php', 'existing');
        $command = new MakeControllerCommand();
        $result = $command->handle(['Page']);

        $this->assertSame('error', $result['type']);
    }

    public function testCreatesControllerDirectoryIfMissing(): void
    {
        $this->rrmdir($this->tempDir . '/app/Controllers');
        $command = new MakeControllerCommand();
        $result = $command->handle(['Page']);

        $this->assertSame('success', $result['type']);
        $this->assertFileExists($this->tempDir . '/app/Controllers/PageController.php');
    }
}
