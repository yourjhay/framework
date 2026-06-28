<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeAuthCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/controller/*.stub') as $filename) {
            $dest = "app/Controllers/Auth/" . str_replace('.stub', '.php', basename($filename));
            if (!file_exists('app/Controllers/Auth')) {
                mkdir('app/Controllers/Auth', 0777, true);
            }
            copy($filename, $dest);
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/helper/*.stub') as $filename) {
            $dest = "app/Helper/Auth/" . str_replace('.stub', '.php', basename($filename));
            if (!file_exists('app/Helper/Auth')) {
                mkdir('app/Helper/Auth', 0777, true);
            }
            copy($filename, $dest);
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/model/*.stub') as $filename) {
            $dest = "app/Models/" . str_replace('.stub', '.php', basename($filename));
            copy($filename, $dest);
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/Views/Auth/*.html') as $filename) {
            $dest = "app/Views/auth/" . basename($filename);
            if (!file_exists('app/Views/auth')) {
                mkdir('app/Views/auth', 0777, true);
            }
            copy($filename, $dest);
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/Views/layouts/*.html') as $filename) {
            $dest = "app/Views/layouts/" . basename($filename);
            if (!file_exists($dest)) {
                copy($filename, $dest);
            }
        }

        foreach (glob('./vendor/simplyphp/framework/src/AuthScaffolding/request/*.stub') as $filename) {
            $dest = "app/Requests/" . str_replace('.stub', '.php', basename($filename));
            copy($filename, $dest);
        }

        $routeFile = './vendor/simplyphp/framework/src/AuthScaffolding/routes.simply';
        $file = file_get_contents($routeFile, FILE_USE_INCLUDE_PATH);
        $mainRoute = "./app/Routes.php";
        file_put_contents($mainRoute, PHP_EOL . $file, FILE_APPEND | LOCK_EX);

        return ['type' => 'success', 'message' => 'Auth scaffolding created successfully'];
    }
}
