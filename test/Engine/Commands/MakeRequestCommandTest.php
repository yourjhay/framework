<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\MakeRequestCommand;

class MakeRequestCommandTest extends TestCase
{
    private string $tempDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/make-request-test-' . getmypid();
        mkdir($this->tempDir . '/app/Requests', 0777, true);
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

    public function testCreatesRequestFile(): void
    {
        $command = new MakeRequestCommand();
        $result = $command->handle(['StoreUser']);

        $this->assertSame('success', $result['type']);
        $this->assertFileExists($this->tempDir . '/app/Requests/StoreUser.php');
    }

    public function testCreatedRequestContainsNamespace(): void
    {
        $command = new MakeRequestCommand();
        $command->handle(['LoginRequest']);

        $contents = file_get_contents($this->tempDir . '/app/Requests/LoginRequest.php');
        $this->assertStringContainsString('namespace App\Requests;', $contents);
    }

    public function testCreatedRequestExtendsFormRequest(): void
    {
        $command = new MakeRequestCommand();
        $command->handle(['LoginRequest']);

        $contents = file_get_contents($this->tempDir . '/app/Requests/LoginRequest.php');
        $this->assertStringContainsString('extends FormRequest', $contents);
    }

    public function testCreatedRequestHasAuthorizeMethod(): void
    {
        $command = new MakeRequestCommand();
        $command->handle(['LoginRequest']);

        $contents = file_get_contents($this->tempDir . '/app/Requests/LoginRequest.php');
        $this->assertStringContainsString('public function authorize', $contents);
    }

    public function testCreatedRequestHasRulesMethod(): void
    {
        $command = new MakeRequestCommand();
        $command->handle(['LoginRequest']);

        $contents = file_get_contents($this->tempDir . '/app/Requests/LoginRequest.php');
        $this->assertStringContainsString('public function rules', $contents);
    }

    public function testCreatedRequestHasMessagesMethod(): void
    {
        $command = new MakeRequestCommand();
        $command->handle(['LoginRequest']);

        $contents = file_get_contents($this->tempDir . '/app/Requests/LoginRequest.php');
        $this->assertStringContainsString('public function messages', $contents);
    }

    public function testCreatedRequestHasFieldsMethod(): void
    {
        $command = new MakeRequestCommand();
        $command->handle(['LoginRequest']);

        $contents = file_get_contents($this->tempDir . '/app/Requests/LoginRequest.php');
        $this->assertStringContainsString('public function fields', $contents);
    }

    public function testRequestNameConvertedToStudlyCaps(): void
    {
        $command = new MakeRequestCommand();
        $command->handle(['store-user']);

        $this->assertFileExists($this->tempDir . '/app/Requests/StoreUser.php');
    }

    public function testReturnsErrorWhenNameMissing(): void
    {
        $command = new MakeRequestCommand();
        $result = $command->handle([]);

        $this->assertSame('error', $result['type']);
    }

    public function testReturnsErrorWhenRequestAlreadyExists(): void
    {
        file_put_contents($this->tempDir . '/app/Requests/StoreUser.php', 'existing');
        $command = new MakeRequestCommand();
        $result = $command->handle(['StoreUser']);

        $this->assertSame('error', $result['type']);
    }

    public function testCreatesRequestDirectoryIfMissing(): void
    {
        $this->rrmdir($this->tempDir . '/app/Requests');
        $command = new MakeRequestCommand();
        $result = $command->handle(['StoreUser']);

        $this->assertSame('success', $result['type']);
        $this->assertFileExists($this->tempDir . '/app/Requests/StoreUser.php');
    }
}
