<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeControllerCommand implements CommandInterface
{
    private string $controllerPath = './app/Controllers/';
    private string $modelPath = 'app/Models/';

    public function handle(array $args): ?array
    {
        $name = $args[0] ?? null;
        $option = $args[1] ?? null;
        if (!$name) {
            return ['type' => 'error', 'message' => 'Controller name must be defined'];
        }
        if (!preg_match("/controller$/i", $name)) {
            $name = $name . 'Controller';
        }
        $name = self::convertToStudlyCaps($name);
        $filePath = "$this->controllerPath$name.php";

        if ($option === '-r' || $option === '-rm') {
            $stubFile = $option === '-rm'
                ? __DIR__ . '/../Stubs/controller-resource-model.stub'
                : __DIR__ . '/../Stubs/controller-resource.stub';
            $stub = file_get_contents($stubFile);
            $stub = str_replace('{{className}}', $name, $stub);
        } else {
            $stub = file_get_contents(__DIR__ . '/../Stubs/controller.stub');
            $stub = str_replace('{{className}}', $name, $stub);
        }

        if ($option === '-rm' || $option === '-m') {
            $modelName = str_replace('Controller', '', $name);
            $modelClass = self::convertToStudlyCaps($modelName);
            $modelStub = file_get_contents(__DIR__ . '/../Stubs/model.stub');
            $modelStub = str_replace(
                ['{{className}}', '{{tableName}}'],
                [$modelClass, strtolower($modelClass) . 's'],
                $modelStub
            );
            $modelFilePath = "{$this->modelPath}$modelClass.php";
            if (!file_exists($modelFilePath)) {
                $dir = dirname($modelFilePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($modelFilePath, $modelStub);
            }
        }

        if (file_exists($filePath)) {
            return ['type' => 'error', 'message' => "$name Controller is already exist!"];
        }
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, $stub);
        return ['type' => 'success', 'message' => "Controller $name created successfuly"];
    }

    private static function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
}
