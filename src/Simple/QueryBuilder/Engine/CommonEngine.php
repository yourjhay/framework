<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Engine;

use function sprintf;

class CommonEngine extends BasicEngine
{
    public function escapeIdentifier(string $identifier): string
    {
        return sprintf('"%s"', $identifier);
    }
}
