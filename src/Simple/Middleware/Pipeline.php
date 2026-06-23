<?php

namespace Simple\Middleware;

use Simple\Request;
use Closure;

class Pipeline
{
    protected array $middleware = [];

    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    public function send(Request $request, Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn($next, $class) => fn($request) => (new $class)->handle($request, $next),
            $destination
        );

        return $pipeline($request);
    }
}
