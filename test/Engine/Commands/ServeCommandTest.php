<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\ServeCommand;

class ServeCommandTest extends TestCase
{
    public function testReturnsNull(): void
    {
        $command = new ServeCommand();
        ob_start();
        $result = $command->handle(['localhost', '8000']);
        ob_get_clean();

        $this->assertNull($result);
    }

    public function testOutputShowsServerAddress(): void
    {
        $command = new ServeCommand();
        ob_start();
        $command->handle(['localhost', '8000']);
        $output = ob_get_clean();

        $this->assertStringContainsString('http://localhost:8000', $output);
    }

    public function testOutputShowsServerAddressWithPortPrefix(): void
    {
        $command = new ServeCommand();
        ob_start();
        $command->handle(['localhost', 'port=8080']);
        $output = ob_get_clean();

        $this->assertStringContainsString('http://localhost:8080', $output);
    }

    public function testUsesDefaultHostAndPortWhenNoneProvided(): void
    {
        $command = new ServeCommand();
        ob_start();
        $command->handle([]);
        $output = ob_get_clean();

        $this->assertStringContainsString('http://localhost:8000', $output);
    }

    public function testIncludesCancelInstructions(): void
    {
        $command = new ServeCommand();
        ob_start();
        $command->handle(['localhost', '8000']);
        $output = ob_get_clean();

        $this->assertStringContainsString('CTRL+C', $output);
    }
}
