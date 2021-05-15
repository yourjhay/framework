<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Query\Capability;

use Simple\QueryBuilder\Query\UnionQuery;
use Simple\QueryBuilder\StatementInterface;

trait CanUnion
{
    public function union(StatementInterface $right): UnionQuery
    {
        return new UnionQuery($this->engine, $this, $right);
    }

    public function unionAll(StatementInterface $right): UnionQuery
    {
        return $this->union($right)->all();
    }
}
