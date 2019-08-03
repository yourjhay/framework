<?php
declare(strict_types=1);

namespace Simple\QueryBuilder\Query\SqlServer;

use Simple\QueryBuilder\ExpressionInterface;
use Simple\QueryBuilder\Query;

use function Simple\QueryBuilder\literal;

class DeleteQuery extends Query\DeleteQuery
{
    protected function startExpression(): ExpressionInterface
    {
        $query = parent::startExpression();
        if (is_int($this->limit)) {
            $query = $query->append('TOP(%d)', literal($this->limit));
        }
        return $query;
    }

    protected function applyLimit(ExpressionInterface $query): ExpressionInterface
    {
        return $query;
    }
}
