<?php

namespace Simple\Engine\Commands;

use Simple\Database\Connection;
use Simple\Engine\ConsoleOutput;
use Simple\Engine\Contracts\CommandInterface;

class SeedModelCommand implements CommandInterface
{
    use Connection;

    private ConsoleOutput $output;

    public function __construct()
    {
        $this->output = new ConsoleOutput;
    }

    public function handle(array $args): ?array
    {
        $modelName = $args[0] ?? null;
        if (!$modelName) {
            return ['type' => 'error', 'message' => 'Usage: <model>:seed (e.g. user:seed)'];
        }

        $className = 'App\\Models\\' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $modelName)));

        if (!class_exists($className)) {
            return ['type' => 'error', 'message' => "Model [$className] not found"];
        }

        $model = new $className;
        $fillable = $model->getFillable();

        if (empty($fillable)) {
            return ['type' => 'error', 'message' => "Model [$className] has no fillable attributes"];
        }

        $this->connect();

        start:
        echo "seeding {$modelName}..." . PHP_EOL;

        $data = [];
        foreach ($fillable as $field) {
            if (in_array($field, ['email_verified_at', 'email_verified', 'verified_at', 'email_verified_date'])) {
                echo " $field (YYYY-MM-DD HH:MM:SS or blank for null): ";
                $value = trim(fgets(STDIN));
                $data[$field] = $value !== '' ? $value : null;
            } elseif (str_contains($field, 'password')) {
                echo " $field (will be auto-hashed): ";
                $value = trim(fgets(STDIN));
                $data[$field] = password_hash($value, PASSWORD_BCRYPT);
            } else {
                echo " $field: ";
                $data[$field] = trim(fgets(STDIN));
            }
        }

        try {
            $className::create($data);
            echo $this->output->print_o(PHP_EOL . " Seeding successful ", "black", "green");
        } catch (\Throwable $e) {
            echo $this->output->print_o(PHP_EOL . " Seeding failed: " . $e->getMessage(), "white", "red");
        }

        echo PHP_EOL . "Do you want to seed another entry? (yes|no): ";
        $ans = trim(fgets(STDIN));
        if ($ans === 'yes') {
            goto start;
        }
        return null;
    }
}
