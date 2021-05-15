<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Query\Capability;

use Simple\QueryBuilder\ExpressionInterface;

use function is_int;
use function Simple\QueryBuilder\literal;

trait HasLimit
{
    protected ?int $limit = null;

    public function limit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    protected function applyLimit(ExpressionInterface $query): ExpressionInterface
    {
        return is_int($this->limit) ? $query->append('LIMIT %d', literal($this->limit)) : $query;
    }
}
