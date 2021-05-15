<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Query\Capability;

use Simple\QueryBuilder\ExpressionInterface;

use function Simple\QueryBuilder\listing;
use function Simple\QueryBuilder\order;

trait HasOrderBy
{
    protected array $orderBy = [];

    /**
     * @param mixed $column
     */
    public function orderBy($column, string $direction = ''): self
    {
        if (! $column) {
            $this->orderBy = [];

            return $this;
        }

        $this->orderBy[] = order($column, $direction);

        return $this;
    }

    protected function applyOrderBy(ExpressionInterface $query): ExpressionInterface
    {
        return $this->orderBy ? $query->append('ORDER BY %s', listing($this->orderBy)) : $query;
    }
}
