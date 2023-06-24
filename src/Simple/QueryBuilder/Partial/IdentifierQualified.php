<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Partial;

use Simple\QueryBuilder\EngineInterface;
use Simple\QueryBuilder\StatementInterface;

final class IdentifierQualified implements StatementInterface
{
    /** @var StatementInterface[] */
    private array $identifiers;

    public function __construct(
        StatementInterface ...$identifiers
    ) {
        $this->identifiers = $identifiers;
    }

    public function sql(EngineInterface $engine): string
    {
        return $engine->flattenSql('.', ...$this->identifiers);
    }

    public function params(EngineInterface $engine): array
    {
        return $engine->flattenParams(...$this->identifiers);
    }
}
