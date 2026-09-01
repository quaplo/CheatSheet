<?php

declare(strict_types=1);

/** Slevový kód. Parametrizované pravidlo — jedna třída, mnoho kódů. */
final readonly class PromoCodeRule implements DiscountRule
{
    public function __construct(
        private string $code,
        private int $percent,
    ) {
    }

    public function name(): string
    {
        return sprintf('Kód %s −%d %%', $this->code, $this->percent);
    }

    public function priority(): int
    {
        return 90;
    }

    public function appliesTo(DiscountContext $context): bool
    {
        return $context->promoCode === $this->code;
    }

    public function discountFor(DiscountContext $context): int
    {
        return intdiv($context->orderTotalInCents * $this->percent, 100);
    }
}
