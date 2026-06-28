<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\HelpCommand;

class HelpCommandTest extends TestCase
{
    public function testHelpOutputContainsCommandList(): void
    {
        $command = new HelpCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('AVAILABLE COMMANDS:', $output);
    }

    public function testHelpOutputContainsServe(): void
    {
        $command = new HelpCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('serve', $output);
    }

    public function testHelpOutputContainsRouteList(): void
    {
        $command = new HelpCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('route:list', $output);
    }

    public function testHelpOutputContainsKeyGenerate(): void
    {
        $command = new HelpCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('key:generate', $output);
    }

    public function testHelpOutputContainsMakeCommands(): void
    {
        $command = new HelpCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('make:controller', $output);
        $this->assertStringContainsString('make:model', $output);
        $this->assertStringContainsString('make:auth', $output);
        $this->assertStringContainsString('make:request', $output);
    }

    public function testHelpOutputContainsSeed(): void
    {
        $command = new HelpCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('user:seed', $output);
    }

    public function testHelpOutputContainsMigrate(): void
    {
        $command = new HelpCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('migrate', $output);
    }

    public function testHelpOutputContainsCacheClear(): void
    {
        $command = new HelpCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('cache:clear', $output);
    }

    public function testHelpOutputContainsSessionDestroy(): void
    {
        $command = new HelpCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('session:destroy', $output);
    }

    public function testHelpReturnsNull(): void
    {
        $command = new HelpCommand();
        ob_start();
        $result = $command->handle([]);
        ob_get_clean();

        $this->assertNull($result);
    }

    public function testHelpOutputStartsWithNewline(): void
    {
        $command = new HelpCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringStartsWith(PHP_EOL, $output);
    }
}
