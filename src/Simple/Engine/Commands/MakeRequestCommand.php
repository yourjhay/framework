<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeRequestCommand implements CommandInterface
{
    private string $requestPath = 'app/Requests/';

    public function handle(array $args): ?array
    {
        $name = $args[0] ?? null;
        if (!$name) {
            return ['type' => 'error', 'message' => 'Request name must be defined'];
        }
        $name = self::convertToStudlyCaps($name);
        $stub = file_get_contents(__DIR__ . '/../Stubs/request.stub');
        $stub = str_replace('{{className}}', $name, $stub);
        $dir = $this->requestPath;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filePath = "$dir$name.php";
        if (file_exists($filePath)) {
            return ['type' => 'error', 'message' => "$name Request is already exist!"];
        }
        file_put_contents($filePath, $stub);
        return ['type' => 'success', 'message' => "Request $name created successfully"];
    }

    private static function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
}
