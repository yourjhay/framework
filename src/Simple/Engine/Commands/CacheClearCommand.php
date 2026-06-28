<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;

class CacheClearCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        $cacheDir = './storage/framework/cache/views';
        if (!is_dir($cacheDir)) {
            return ['type' => 'error', 'message' => 'Views cache directory not found.'];
        }
        $files = glob($cacheDir . '/*');
        if ($files === false) {
            return ['type' => 'error', 'message' => 'Error reading views cache directory.'];
        }
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return ['type' => 'success', 'message' => 'Views cache cleared successfully.'];
    }
}
