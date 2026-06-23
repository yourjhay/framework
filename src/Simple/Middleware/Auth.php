<?php

namespace Simple\Middleware;

use Simple\Request;
use Simple\Session;
use Closure;

class Auth implements Middleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Session::get('user') === null) {
            throw new \RuntimeException('Unauthenticated', 401);
        }

        return $next($request);
    }
}
