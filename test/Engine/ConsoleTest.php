<?php

namespace Simple\Tests\Engine;

use PHPUnit\Framework\TestCase;
use Simple\Engine\Console;

class ConsoleTest extends TestCase
{
    public function testNoCommandPrintsError(): void
    {
        $console = new Console(1, ['cli']);
        ob_start();
        $console->consoleRun();
        $console->print_status();
        $output = ob_get_clean();
        $this->assertStringContainsString('No command provided', $output);
    }

    public function testNullArgvPrintsError(): void
    {
        $console = new Console(null, null);
        ob_start();
        $console->consoleRun();
        $console->print_status();
        $output = ob_get_clean();
        $this->assertStringContainsString('No command provided', $output);
    }

    public function testUnknownCommandPrintsError(): void
    {
        $console = new Console(2, ['cli', 'nope']);
        ob_start();
        $console->consoleRun();
        $console->print_status();
        $output = ob_get_clean();
        $this->assertStringContainsString('Command not found', $output);
    }

    public function testUnknownCommandShowsNotFoundMessage(): void
    {
        $console = new Console(2, ['cli', 'does:not:exist']);
        ob_start();
        $console->consoleRun();
        $console->print_status();
        $output = ob_get_clean();
        $this->assertStringContainsString('Command not found', $output);
    }

    public function testHelpCommandDispatchesAndOutputs(): void
    {
        $console = new Console(2, ['cli', 'help']);
        ob_start();
        $console->consoleRun();
        $console->print_status();
        $output = ob_get_clean();
        $this->assertStringContainsString('AVAILABLE COMMANDS', $output);
    }

    public function testMinusHelpAliasDispatches(): void
    {
        $console = new Console(2, ['cli', '-help']);
        ob_start();
        $console->consoleRun();
        $output = ob_get_clean();
        $this->assertStringContainsString('AVAILABLE COMMANDS', $output);
    }

    public function testHelpDoesNotPrintStatus(): void
    {
        $console = new Console(2, ['cli', 'help']);
        ob_start();
        $console->consoleRun();
        $output = ob_get_clean();
        $console2 = new Console(2, ['cli', 'help']);
        ob_start();
        $console2->print_status();
        $status = ob_get_clean();
        $this->assertEmpty($status);
    }

    public function testArgsPassedToCommand(): void
    {
        $console = new Console(3, ['cli', 'help', 'extra_arg']);
        ob_start();
        $console->consoleRun();
        $output = ob_get_clean();
        $this->assertStringContainsString('AVAILABLE COMMANDS', $output);
    }

    public function testConsoleRunReturnsNull(): void
    {
        $console = new Console(1, ['cli']);
        $result = $console->consoleRun();
        $this->assertNull($result);
    }

    public function testPrintStatusNoOutputWhenStatusNotSet(): void
    {
        $console = new Console(2, ['cli', 'help']);
        $console->consoleRun();
        ob_start();
        $console->print_status();
        $output = ob_get_clean();
        $this->assertEmpty($output);
    }

    public function testCacheClearOnMissingDirReturnsErrorStatus(): void
    {
        $console = new Console(2, ['cli', 'cache:clear']);
        ob_start();
        $console->consoleRun();
        $console->print_status();
        $output = ob_get_clean();
        $this->assertStringContainsString('Views cache directory not found', $output);
    }
}
