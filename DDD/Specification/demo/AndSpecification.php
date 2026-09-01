<?php

declare(strict_types=1);

/**
 * Obě pravidla musí platit.
 *
 * Všimni si reasonsForFailure(): sesbírá důvody z obou stran, takže
 * u složeného pravidla víš přesně, které jeho části selhaly.
 */
final class AndSpecification extends OrderSpecification
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
            && $this->right->isSatisfiedBy($order);
    }

    public function describe(): string
    {
        return sprintf('%s a zároveň %s', $this->describeOperand($this->left), $this->describeOperand($this->right));
    }

    public function reasonsForFailure(Order $order): array
    {
        return [
            ...$this->left->reasonsForFailure($order),
            ...$this->right->reasonsForFailure($order),
        ];
    }
}
