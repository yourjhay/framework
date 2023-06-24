<?php

declare(strict_types=1);

namespace Simple\QueryBuilder;

interface CriteriaInterface extends StatementInterface
{
    public function and(CriteriaInterface $right): CriteriaInterface;

    public function or(CriteriaInterface $right): CriteriaInterface;
}
