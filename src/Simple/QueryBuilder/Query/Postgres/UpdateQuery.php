<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Query\Postgres;

use Simple\QueryBuilder\ExpressionInterface;
use Simple\QueryBuilder\Query;

class UpdateQuery extends Query\UpdateQuery
{
    use Query\Capability\HasReturning;

    public function asExpression(): ExpressionInterface
    {
        $query = parent::asExpression();
        $query = $this->applyReturning($query);

        return $query;
    }
}
