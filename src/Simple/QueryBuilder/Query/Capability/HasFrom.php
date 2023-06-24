<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Query\Capability;

use Simple\QueryBuilder\ExpressionInterface;

use function array_merge;
use function Simple\QueryBuilder\identifyAll;
use function Simple\QueryBuilder\listing;

trait HasFrom
{
    protected array $from = [];

    /**
     * @param mixed ...$tables
     */
    public function from(...$tables): self
    {
        $this->from = identifyAll($tables);

        return $this;
    }

    /**
     * @param mixed ...$tables
     */
    public function addFrom(...$tables): self
    {
        return $this->from(...array_merge($this->from, $tables));
    }

    protected function applyFrom(ExpressionInterface $query): ExpressionInterface
    {
        return $this->from ? $query->append('FROM %s', listing($this->from)) : $query;
    }
}
