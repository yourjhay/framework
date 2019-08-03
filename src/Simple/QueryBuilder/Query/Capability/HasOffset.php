<?php
declare(strict_types=1);

namespace Simple\QueryBuilder\Query\Capability;

use Simple\QueryBuilder\ExpressionInterface;

use function Simple\QueryBuilder\literal;

trait HasOffset
{
    /** @var int|null */
    protected $offset;

    public function offset(?int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    protected function applyOffset(ExpressionInterface $query): ExpressionInterface
    {
        return is_int($this->offset) ? $query->append('OFFSET %d', literal($this->offset)): $query;
    }
}
