<?php

namespace Simple\Engine\Commands;

use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;

class ServeCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $output = new ConsoleOutput;
        $host = $args[0] ?? 'localhost';
        $port = $args[1] ?? '8000';
        if ($port !== null && str_starts_with($port, 'port=')) {
            $port = substr($port, 5);
        }
        $command = "php -S $host:$port -t public/";
        echo $output->print_o("Simply Development Server started at: http://$host:$port" . PHP_EOL, 'green', 'white');
        echo $output->print_o("Press CTRL+C to cancel" . PHP_EOL, 'green', 'black');
        exec($command, $worked, $output);
        return null;
    }
}
