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
        echo $output->print_o(" make:auth", 'green', 'black') . " This creates a authentication scaffoldings for your application" . PHP_EOL;
        echo $output->print_o(" make:request RequestName", 'green', 'black') . " This creates a form request class for validation" . PHP_EOL;
        echo $output->print_o(" user:seed", 'green', 'black') . " Insert data to users table" . PHP_EOL;
        echo $output->print_o(" migrate sqlfilename", 'green', 'black') . " Migrate the sqlfiles in database folder(for mysql only)" . PHP_EOL;
        echo $output->print_o(" migrate users", 'green', 'black') . " This creates users table in you database(for sqlite and mysql)" . PHP_EOL;
        echo $output->print_o(" migrate -c \"your_query\"", 'green', 'black') . " Communicate with sqlite database" . PHP_EOL;
        echo $output->print_o(" session:destroy", 'green', 'black') . " Destroys all active session" . PHP_EOL;
        echo $output->print_o(" cache:clear", 'green', 'black') . " Clears the Twig views cache" . PHP_EOL;
        echo PHP_EOL;
        return null;
    }
}
