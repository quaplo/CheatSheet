<?php

declare(strict_types=1);

/** Stačí, když platí jedno z pravidel. */
final class OrSpecification extends OrderSpecification
{
    public function __construct(
        private readonly OrderSpecification $left,
        private readonly OrderSpecification $right,
    ) {
    }

    protected function needsParentheses(): bool
    {
        return true;
    }

    public function isSatisfiedBy(Order $order): bool
    {
        return $this->left->isSatisfiedBy($order)
            || $this->right->isSatisfiedBy($order);
    }

    public function describe(): string
    {
        return sprintf('%s nebo %s', $this->describeOperand($this->left), $this->describeOperand($this->right));
    }
}
