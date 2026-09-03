<?php

declare(strict_types=1);

/** První objednávka: 200 Kč, ale jen od 1 000 Kč výše. */
final readonly class FirstOrderRule implements DiscountRule
{
    private const int DISCOUNT = 20000;
    private const int MINIMUM = 100000;

    public function name(): string
    {
        return 'První objednávka −200 Kč';
    }

    public function priority(): int
    {
        return 80;
    }

    public function appliesTo(DiscountContext $context): bool
    {
        return $context->isFirstOrder && $context->orderTotalInCents >= self::MINIMUM;
    }

    public function discountFor(DiscountContext $context): int
    {
        return self::DISCOUNT;
    }
}
