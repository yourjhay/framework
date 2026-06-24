<?php

namespace Simple\Middleware;

use Simple\Request;
use Closure;

class RateLimit implements Middleware
{
    private int $maxAttempts;
    private int $decaySeconds;
    private string $storageDir;

    public function __construct()
    {
        $this->maxAttempts = \Simple\Config::get('security.rate_limit_max', 5);
        $this->decaySeconds = \Simple\Config::get('security.rate_limit_decay', 60);
        $this->storageDir = \Simple\Config::get('security.rate_limit_storage', '../app/storage/framework/rate-limit');
    }

    public function handle(Request $request, Closure $next)
    {
        $key = $this->buildKey();

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }

        $file = $this->storageDir . '/' . $key . '.json';
        $data = ['attempts' => 0, 'first_attempt' => time()];

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $elapsed = time() - $data['first_attempt'];

            if ($elapsed > $this->decaySeconds) {
                $data = ['attempts' => 1, 'first_attempt' => time()];
                file_put_contents($file, json_encode($data));
                return $next($request);
            }

            $data['attempts']++;
            file_put_contents($file, json_encode($data));

            if ($data['attempts'] > $this->maxAttempts) {
                http_response_code(429);
                header('Retry-After: ' . ($this->decaySeconds - $elapsed));
                throw new \RuntimeException('Too many requests', 429);
            }
        } else {
            $data['attempts'] = 1;
            file_put_contents($file, json_encode($data));
        }

        return $next($request);
    }

    public static function clear(): void
    {
        $storageDir = \Simple\Config::get('security.rate_limit_storage', '../app/storage/framework/rate-limit');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $route = $_SERVER['REQUEST_URI'] ?? '/';
        $signature = md5($route);
        $key = $ip . '-' . $signature;
        $file = $storageDir . '/' . $key . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function buildKey(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $route = $_SERVER['REQUEST_URI'] ?? '/';
        $signature = md5($route);
        return $ip . '-' . $signature;
    }
}
