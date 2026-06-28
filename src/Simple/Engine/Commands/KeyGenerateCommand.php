<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;
use Simple\Security\Encryption;

class KeyGenerateCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $key = Encryption::generateKey();
        $envFile = './.env';
        if (!file_exists($envFile)) {
            return ['type' => 'error', 'message' => 'Failed to generate application key: .env not found'];
        }
        $contents = file_get_contents($envFile);
        if (preg_match('/^APP_KEY=.*$/m', $contents)) {
            $contents = preg_replace('/^APP_KEY=.*$/m', "APP_KEY=$key", $contents);
        } else {
            $contents .= PHP_EOL . "APP_KEY=$key" . PHP_EOL;
        }
        file_put_contents($envFile, $contents);
        return ['type' => 'success', 'message' => 'Application Key Generated Successfully!'];
    }
}
