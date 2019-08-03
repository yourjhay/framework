<?php
declare(strict_types=1);

namespace Simple\QueryBuilder\Query\Capability;

use Simple\QueryBuilder\ExpressionInterface;
use Simple\QueryBuilder\StatementInterface;

use function Simple\QueryBuilder\identifyAll;
use function Simple\QueryBuilder\listing;

trait HasFrom
{
    /** @var StatementInterface[] */
    protected $from = [];

    public function from(...$tables): self
    {
        $this->from = identifyAll($tables);
        return $this;
    }

    public function addFrom(...$tables): self
    {
        return $this->from(...array_merge($this->from, $tables));
    }

    protected function applyFrom(ExpressionInterface $query): ExpressionInterface
    {
        return $this->from ? $query->append('FROM %s', listing($this->from)) : $query;
    }
}
