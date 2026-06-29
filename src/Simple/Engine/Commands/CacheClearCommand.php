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
        $files = array_merge(
            glob($cacheDir . '/*.php') ?: [],
            glob($cacheDir . '/**/*.php') ?: []
        );
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $dirs = glob($cacheDir . '/*', GLOB_ONLYDIR) ?: [];
        foreach ($dirs as $dir) {
            if (is_dir($dir) && count(scandir($dir)) <= 2) {
                rmdir($dir);
            }
        }
        return ['type' => 'success', 'message' => 'Views cache cleared successfully.'];
    }
}
