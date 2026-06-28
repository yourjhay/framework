<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeModelCommand implements CommandInterface
{
    private string $modelPath = 'app/Models/';

    public function handle(array $args): ?array
    {
        $name = $args[0] ?? null;
        if (!$name) {
            return ['type' => 'error', 'message' => 'Model name must be defined'];
        }
        $name = self::convertToStudlyCaps($name);
        $stub = file_get_contents(__DIR__ . '/../Stubs/model.stub');
        $stub = str_replace(
            ['{{className}}', '{{tableName}}'],
            [$name, strtolower($name) . 's'],
            $stub
        );
        if (file_exists("$this->modelPath$name.php")) {
            return ['type' => 'error', 'message' => "$name Model is already exist!"];
        }
        $dir = dirname("$this->modelPath$name.php");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents("$this->modelPath$name.php", $stub);
        return ['type' => 'success', 'message' => "Model $name created successfuly"];
    }

    private static function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
}
