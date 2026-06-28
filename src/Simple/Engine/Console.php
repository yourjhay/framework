<?php
namespace Simple\Engine;

use Simple\Engine\ConsoleOutput as co;
use Simple\Engine\Contracts\CommandInterface;

class Console
{
    private array $argv;
    private ?string $status;
    private co $output;

    public function __construct($argc, $argv)
    {
        $this->status = null;
        $this->argv = $argv ?? [];
        $this->output = new co;
    }

    public function consoleRun(): void
    {
        $name = $this->argv[1] ?? null;
        if (!$name) {
            $this->status = 'error: No command provided.' . PHP_EOL;
            return;
        }

        $class = CommandRegistry::get($name);
        if (!$class) {
            $this->status = 'error: ===== Command not found. =====' . PHP_EOL;
            return;
        }

        $command = new $class;
        if (!$command instanceof CommandInterface) {
            $this->status = 'error: Invalid command class.' . PHP_EOL;
            return;
        }

        $result = $command->handle(array_slice($this->argv, 2));
        if ($result !== null) {
            $this->status = $result['type'] . ': ' . $result['message'] . PHP_EOL;
        }
    }

    public function print_status(): void
    {
        if ($this->status === null) {
            return;
        }
        $parts = explode(':', $this->status);
        if ($parts[0] === 'error') {
            echo $this->output->print_o($parts[1], "white", "red");
        } elseif ($parts[0] === 'success') {
            echo $this->output->print_o($parts[1], "black", "green");
        }
    }
}
