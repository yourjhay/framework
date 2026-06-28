<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class MakeObserverCommand implements CommandInterface
{
    private string $observerPath = 'app/Observers/';

    public function handle(array $args): ?array
    {
        $model = $args[0] ?? null;
        if (!$model) {
            return ['type' => 'error', 'message' => 'Model name must be defined'];
        }
        $model = self::convertToStudlyCaps($model);
        $stub = file_get_contents(__DIR__ . '/../Stubs/observer.stub');
        $stub = str_replace('{{className}}', $model, $stub);
        $filePath = "{$this->observerPath}{$model}Observer.php";
        if (file_exists($filePath)) {
            return ['type' => 'error', 'message' => "$model Observer is already exist!"];
        }
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, $stub);
        return ['type' => 'success', 'message' => "Observer $model created successfuly"];
    }

    private static function convertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
}
