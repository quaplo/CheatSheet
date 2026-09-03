<?php

declare(strict_types=1);

/** VIP zákazník má 10 % z objednávky. */
final readonly class VipCustomerRule implements DiscountRule
{
    public function name(): string
    {
        return 'VIP zákazník 10 %';
    }

    public function priority(): int
    {
        return 100;
    }

    public function appliesTo(DiscountContext $context): bool
    {
        return $context->isVipCustomer;
    }

    public function discountFor(DiscountContext $context): int
    {
        return intdiv($context->orderTotalInCents, 10);
    }
}
