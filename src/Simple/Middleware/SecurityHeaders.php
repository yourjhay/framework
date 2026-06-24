<?php

namespace Simple\Middleware;

use Simple\Request;
use Closure;

class SecurityHeaders implements Middleware
{
    public function handle(Request $request, Closure $next)
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');

        $csp = \Simple\Config::get('security.csp_policy', "default-src 'self'");
        header("Content-Security-Policy: $csp");

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        return $next($request);
    }
}
