<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Builder;

use Simple\QueryBuilder\CriteriaInterface;
use Simple\QueryBuilder\Partial\LikeBegins;
use Simple\QueryBuilder\Partial\LikeContains;
use Simple\QueryBuilder\Partial\LikeEnds;
use Simple\QueryBuilder\StatementInterface;

use function Simple\QueryBuilder\criteria;

class LikeBuilder
{
    private StatementInterface $statement;

    public function __construct(StatementInterface $statement)
    {
        $this->statement = $statement;
    }

    public function begins(string $value): CriteriaInterface
    {
        return $this->like(new LikeBegins($value));
    }

    public function notBegins(string $value): CriteriaInterface
    {
        return $this->notLike(new LikeBegins($value));
    }

    public function contains(string $value): CriteriaInterface
    {
        return $this->like(new LikeContains($value));
    }

    public function notContains(string $value): CriteriaInterface
    {
        return $this->notLike(new LikeContains($value));
    }

    public function ends(string $value): CriteriaInterface
    {
        return $this->like(new LikeEnds($value));
    }

    public function notEnds(string $value): CriteriaInterface
    {
        return $this->notLike(new LikeEnds($value));
    }

    protected function like(StatementInterface $value): CriteriaInterface
    {
        return criteria('%s LIKE %s', $this->statement, $value);
    }

    protected function notLike(StatementInterface $value): CriteriaInterface
    {
        return criteria('%s NOT LIKE %s', $this->statement, $value);
    }
}
