<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Query\SqlServer;

use Simple\QueryBuilder\ExpressionInterface;
use Simple\QueryBuilder\Query;

use function is_int;
use function Simple\QueryBuilder\literal;

class SelectQuery extends Query\SelectQuery
{
    protected function applyOffset(ExpressionInterface $query): ExpressionInterface
    {
        if (is_int($this->offset) && is_int($this->limit)) {
            return $query->append('OFFSET %d ROWS FETCH NEXT %d ROWS ONLY', literal($this->offset), literal($this->limit));
        }

        return $query;
    }

    protected function applyLimit(ExpressionInterface $query): ExpressionInterface
    {
        // SQL Server requires that OFFSET be defined to use LIMIT
        return $query;
    }
}
