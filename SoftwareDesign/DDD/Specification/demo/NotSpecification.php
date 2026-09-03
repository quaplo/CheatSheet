<?php

declare(strict_types=1);

/** Pravidlo naopak. */
final class NotSpecification extends OrderSpecification
{
    public function __construct(
        private readonly OrderSpecification $inner,
    ) {
    }

    protected function needsParentheses(): bool
    {
        return true;
    }

    public function isSatisfiedBy(Order $order): bool
    {
        return $this->inner->isSatisfiedBy($order) === false;
    }

    public function describe(): string
    {
        return sprintf('neplatí, že %s', $this->describeOperand($this->inner));
    }
}
