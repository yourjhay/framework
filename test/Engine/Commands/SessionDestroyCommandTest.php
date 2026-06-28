<?php

namespace Simple\Tests\Engine\Commands;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Commands\SessionDestroyCommand;

class SessionDestroyCommandTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    public function testReturnsSuccessStatus(): void
    {
        $command = new SessionDestroyCommand();
        $result = $command->handle([]);

        $this->assertSame('success', $result['type']);
        $this->assertStringContainsString('destroyed', $result['message']);
    }

    public function testIgnoresArguments(): void
    {
        $command = new SessionDestroyCommand();
        $result = $command->handle(['anything']);

        $this->assertSame('success', $result['type']);
    }
}
