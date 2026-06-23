<?php

namespace Simple\Middleware;

use Simple\Request;
use Closure;

class Csrf implements Middleware
{
    public function handle(Request $request, Closure $next)
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $token = $_SESSION['_token'] ?? '';
        $submittedToken = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if ($token === '' || !hash_equals($token, $submittedToken)) {
            throw new \RuntimeException('CSRF token mismatch', 419);
        }

        return $next($request);
    }
}
