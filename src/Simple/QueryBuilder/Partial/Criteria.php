<?php
declare(strict_types=1);

namespace Simple\QueryBuilder\Partial;

use Simple\QueryBuilder\CriteriaInterface;
use Simple\QueryBuilder\EngineInterface;
use Simple\QueryBuilder\ExpressionInterface;

use function Simple\QueryBuilder\express;

final class Criteria implements CriteriaInterface
{
    /** @var ExpressionInterface */
    private $expression;

    public function __construct(
        ExpressionInterface $expression
    ) {
        $this->expression = $expression;
    }

    public function and(CriteriaInterface $right): CriteriaInterface
    {
        return new self($this->expression->append('AND %s', $right));
    }

    public function or(CriteriaInterface $right): CriteriaInterface
    {
        return new self($this->expression->append('OR %s', $right));
    }

    public function sql(EngineInterface $engine): string
    {
        return $this->expression->sql($engine);
    }

    public function params(EngineInterface $engine): array
    {
        return $this->expression->params($engine);
    }
}