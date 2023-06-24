<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Partial\Parameter;

use Simple\QueryBuilder\EngineInterface;
use Simple\QueryBuilder\StatementInterface;

final class NullParameter implements StatementInterface
{
    public function sql(EngineInterface $engine): string
    {
        return $engine->exportParameter(null);
    }

    public function params(EngineInterface $engine): array
    {
        return [];
    }
}
