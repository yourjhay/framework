<?php
declare(strict_types=1);

namespace Simple\QueryBuilder\Query\Capability;

use Simple\QueryBuilder\ExpressionInterface;
use Simple\QueryBuilder\StatementInterface;

use function Simple\QueryBuilder\identify;

trait HasReturning
{
    /** @var StatementInterface */
    protected $returning;

    public function returning($column): self
    {
        $this->returning = identify($column);
        return $this;
    }

    protected function applyReturning(ExpressionInterface $query): ExpressionInterface
    {
        return $this->returning ? $query->append('RETURNING %s', $this->returning) : $query;
    }
}
