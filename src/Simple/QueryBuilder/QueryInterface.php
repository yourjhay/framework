<?php
declare(strict_types=1);

namespace Simple\QueryBuilder;

interface QueryInterface extends StatementInterface
{
    public function asExpression(): ExpressionInterface;

    public function compile(): Query;
}
