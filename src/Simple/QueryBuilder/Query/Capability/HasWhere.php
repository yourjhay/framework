<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Query\Capability;

use Simple\QueryBuilder\CriteriaInterface;
use Simple\QueryBuilder\ExpressionInterface;

trait HasWhere
{
    protected ?CriteriaInterface $where = null;

    public function where(CriteriaInterface $criteria): self
    {
        $this->where = $criteria;

        return $this;
    }

    public function andWhere(CriteriaInterface $criteria): self
    {
        if ($this->where === null) {
            return $this->where($criteria);
        }

        $this->where = $this->where->and($criteria);

        return $this;
    }

    public function orWhere(CriteriaInterface $criteria): self
    {
        if ($this->where === null) {
            return $this->where($criteria);
        }

        $this->where = $this->where->or($criteria);

        return $this;
    }

    protected function applyWhere(ExpressionInterface $query): ExpressionInterface
    {
        return $this->where ? $query->append('WHERE %s', $this->where) : $query;
    }
}
