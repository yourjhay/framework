<?php

declare(strict_types=1);

namespace Simple\QueryBuilder\Query;

use Simple\QueryBuilder\EngineInterface;
use Simple\QueryBuilder\ExpressionInterface;
use Simple\QueryBuilder\Query;
use Simple\QueryBuilder\QueryInterface;

abstract class AbstractQuery implements QueryInterface
{
    protected EngineInterface $engine;

    public function __construct(
        EngineInterface $engine
    ) {
        $this->engine = $engine;
    }

    abstract public function asExpression(): ExpressionInterface;

    abstract protected function startExpression(): ExpressionInterface;

    public function compile(): Query
    {
        $query = $this->asExpression();

        return new Query(
            $query->sql($this->engine),
            $query->params($this->engine)
        );
    }

    public function sql(EngineInterface $engine): string
    {
        return $this->asExpression()->sql($engine);
    }

    public function params(EngineInterface $engine): array
    {
        return $this->asExpression()->params($engine);
    }
}
