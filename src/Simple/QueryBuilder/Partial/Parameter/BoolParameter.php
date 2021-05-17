<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Partial\Parameter;

use Simple\QueryBuilder\EngineInterface;
use Simple\QueryBuilder\StatementInterface;

final class BoolParameter implements StatementInterface
{
    private bool $value;

    public function __construct(bool $value)
    {
        $this->value = $value;
    }

    public function sql(EngineInterface $engine): string
    {
        return $engine->exportParameter($this->value);
    }

    public function params(EngineInterface $engine): array
    {
        return [];
    }
}
