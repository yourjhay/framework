<?php

namespace Simple\Tests\Engine;

use PHPUnit\Framework\TestCase;
use Simple\Engine\CommandRegistry;
use Simple\Engine\Commands\HelpCommand;

class CommandRegistryTest extends TestCase
{
    public function testGetReturnsClassForRegisteredCommand(): void
    {
        $class = CommandRegistry::get('help');
        $this->assertNotNull($class);
        $this->assertSame(HelpCommand::class, $class);
    }

    public function testGetReturnsNullForUnknownCommand(): void
    {
        $this->assertNull(CommandRegistry::get('unknown:command'));
    }

    public function testGetIsCaseSensitive(): void
    {
        $this->assertNull(CommandRegistry::get('Help'));
        $this->assertNull(CommandRegistry::get('HELP'));
    }

    public function testAllReturnsAllCommands(): void
    {
        $commands = CommandRegistry::all();
        $this->assertArrayHasKey('help', $commands);
        $this->assertArrayHasKey('-help', $commands);
        $this->assertArrayHasKey('migrate', $commands);
        $this->assertArrayHasKey('serve', $commands);
        $this->assertArrayHasKey('make:controller', $commands);
        $this->assertArrayHasKey('make:model', $commands);
        $this->assertArrayHasKey('make:observer', $commands);
        $this->assertArrayHasKey('make:request', $commands);
        $this->assertArrayHasKey('make:auth', $commands);
        $this->assertArrayHasKey('user:seed', $commands);
        $this->assertArrayHasKey('session:destroy', $commands);
        $this->assertArrayHasKey('cache:clear', $commands);
        $this->assertArrayHasKey('key:generate', $commands);
        $this->assertArrayHasKey('route:list', $commands);
    }

    public function testAllReturnsFourteenCommands(): void
    {
        $this->assertCount(14, CommandRegistry::all());
    }

    public function testHelpAndMinusHelpMapToSameClass(): void
    {
        $this->assertSame(
            CommandRegistry::get('help'),
            CommandRegistry::get('-help')
        );
    }

    public function testAllCommandsImplementCommandInterface(): void
    {
        $interface = 'Simple\Engine\Contracts\CommandInterface';
        foreach (CommandRegistry::all() as $name => $class) {
            $this->assertContains(
                $interface,
                class_implements($class),
                "Command '$name' ($class) must implement CommandInterface"
            );
        }
    }
}
