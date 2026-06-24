<?php

namespace Simple\Tests;

use PHPUnit\Framework\TestCase;
use Simple\Application;

class ApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        \Simple\Config::clear();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    public function testBootLoadsConfig(): void
    {
        $tmpDir = sys_get_temp_dir() . '/app-test-' . getmypid();
        @mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir . '/app.php', '<?php return ["name" => env("APP_NAME", "AppBootTest")];');

        putenv('APP_NAME=Override');

        \Simple\Config::clear();
        $app = new Application();
        $app->boot($tmpDir);

        $this->assertEquals('Override', \Simple\Config::get('app.name'));

        putenv('APP_NAME');

        array_map('unlink', glob($tmpDir . '/*'));
        rmdir($tmpDir);
    }

    public function testBootLoadsDefaultsWhenNoEnv(): void
    {
        $tmpDir = sys_get_temp_dir() . '/app-test-default-' . getmypid();
        @mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir . '/app.php', '<?php return ["name" => env("APP_NAME", "DefaultApp")];');

        \Simple\Config::clear();
        $app = new Application();
        $app->boot($tmpDir);

        $this->assertEquals('DefaultApp', \Simple\Config::get('app.name'));

        array_map('unlink', glob($tmpDir . '/*'));
        rmdir($tmpDir);
    }

    public function testBootHandlesMissingConfigDir(): void
    {
        $app = new Application();
        $app->boot('/nonexistent/path');
        $this->assertTrue(true);
    }
}
