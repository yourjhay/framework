<?php
declare(strict_types=1);

namespace Simple\QueryBuilder\Query;

use Simple\QueryBuilder\ExpressionInterface;

use function Simple\QueryBuilder\express;

class DeleteQuery extends AbstractQuery
{
    use Capability\HasFrom;
    use Capability\HasWhere;
    use Capability\HasLimit;

    public function asExpression(): ExpressionInterface
    {
        $query = $this->startExpression();
        $query = $this->applyFrom($query);
        $query = $this->applyWhere($query);
        $query = $this->applyLimit($query);

        return $query;
    }

    protected function startExpression(): ExpressionInterface
    {
        return express('DELETE');
    }
}
