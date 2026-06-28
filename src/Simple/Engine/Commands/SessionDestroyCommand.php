<?php

namespace Simple\Engine\Commands;

use Simple\Engine\Contracts\CommandInterface;
use Simple\Session;

class SessionDestroyCommand implements CommandInterface
{
    public function handle(array $args): ?array
    {
        Session::destroy();
        return ['type' => 'success', 'message' => 'All sessions is destroyed.'];
    }
}
