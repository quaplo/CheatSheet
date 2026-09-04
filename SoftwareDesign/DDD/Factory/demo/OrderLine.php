<?php

declare(strict_types=1);

final readonly class OrderLine
{
    public function __construct(
        public string $sku,
        public int $quantity,
        public Money $unitPrice,
    ) {
    }

    public function subtotal(): Money
    {
        return Money::fromCents($this->unitPrice->amountInCents * $this->quantity);
    }
}
