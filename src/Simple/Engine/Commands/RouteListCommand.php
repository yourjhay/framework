<?php

namespace Simple\Engine\Commands;

use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;
use Simple\Routing\Router;

class RouteListCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        require './app/Routes.php';
        $output = new ConsoleOutput;
        $compile_routes = Router::compiledRoutes();
        echo '-----------------------------------------------------------------' . PHP_EOL;
        foreach ($compile_routes as $key => $val) {
            echo $output->print_o($val['request_method'] . "  '$key'", 'green', 'black') . ' => ' . $val['url'] . PHP_EOL;
            echo '-----------------------------------------------------------------' . PHP_EOL;
        }
        echo $output->print_o(" You have " . count($compile_routes) . " route aliases in your Routes.php", 'black', 'light_gray') . PHP_EOL;
        return null;
    }
}
