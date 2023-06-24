<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Query\MySql;

use Simple\QueryBuilder\ExpressionInterface;
use Simple\QueryBuilder\Query;

class InsertQuery extends Query\InsertQuery
{
    protected bool $ignore = false;

    public function ignore(bool $status): self
    {
        $this->ignore = $status;

        return $this;
    }

    protected function startExpression(): ExpressionInterface
    {
        $query = parent::startExpression();
        if ($this->ignore) {
            $query = $query->append('IGNORE');
        }

        return $query;
    }
}
