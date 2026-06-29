<?php

namespace Simple\Engine\Commands;

use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;
use Simple\Engine\CommandRegistry;

class HelpCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $output = new ConsoleOutput;
        echo PHP_EOL;
        echo ">> php cli + command" . PHP_EOL;
        echo PHP_EOL;
        echo "AVAILABLE COMMANDS:" . PHP_EOL;
        echo $output->print_o(" serve", 'green', 'black') . " This creates a webserver and host you application" . PHP_EOL;
        echo $output->print_o("       options: host port=8080", 'blue', 'black') . " You can set the host and port(optional)" . PHP_EOL;
        echo $output->print_o(" route:list", 'green', 'black') . " Display your route aliases" . PHP_EOL;
        echo $output->print_o(" key:generate", 'green', 'black') . " This creates key for Encryption and Decryption feature" . PHP_EOL;
        echo $output->print_o(" make:controller ControllerName", 'green', 'black') . " This creates a controller in app/Controllers" . PHP_EOL;
        echo $output->print_o("       options: -r or -rm", 'blue', 'black') . " Make the controller a resource(for CRUD), also creates the model automatically" . PHP_EOL;
        echo $output->print_o(" make:model", 'green', 'black') . " This creates a model in app/Models" . PHP_EOL;
        echo $output->print_o(" make:auth", 'green', 'black') . " This creates authentication scaffolding and auto-runs the users migration" . PHP_EOL;
        echo $output->print_o(" make:migration MigrationName", 'green', 'black') . " This creates a migration file in database/migrations" . PHP_EOL;
        echo $output->print_o(" make:request RequestName", 'green', 'black') . " This creates a form request class for validation" . PHP_EOL;
        echo $output->print_o(" <model>:seed", 'green', 'black') . " Seed any model (e.g. user:seed, product:seed)" . PHP_EOL;
        echo $output->print_o(" migrate", 'green', 'black') . " Run all pending class-based migrations" . PHP_EOL;
        echo $output->print_o(" migrate:fresh", 'green', 'black') . " Drop all tables and re-run all migrations" . PHP_EOL;
        echo $output->print_o(" migrate:status", 'green', 'black') . " Show the status of each migration" . PHP_EOL;
        echo $output->print_o(" session:destroy", 'green', 'black') . " Destroys all active session" . PHP_EOL;
        echo $output->print_o(" cache:clear", 'green', 'black') . " Clears the Twig views cache" . PHP_EOL;
        echo PHP_EOL;
        return null;
    }
}
