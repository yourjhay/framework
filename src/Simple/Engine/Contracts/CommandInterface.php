<?php

namespace Simple\Engine\Contracts;

interface CommandInterface
{
    public function handle(array $args): ?array;
}
