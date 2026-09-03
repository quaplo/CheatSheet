<?php

declare(strict_types=1);

/** Položka objednávky — součást agregátu, vlastní repository nemá. */
final readonly class OrderItem
{
    public function __construct(
        public string $product,
        public int $unitPriceInCents,
        public int $quantity,
    ) {
    }

    public function total(): int
    {
        return $this->unitPriceInCents * $this->quantity;
    }
}
