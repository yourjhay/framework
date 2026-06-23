<?php

namespace Simple\Middleware;

use Simple\Request;
use Closure;

interface Middleware
{
    public function handle(Request $request, Closure $next);
}
