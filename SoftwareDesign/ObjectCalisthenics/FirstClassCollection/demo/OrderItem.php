<?php

declare(strict_types=1);

/**
 * Jedna položka objednávky.
 *
 * Peněžní částky jsou v haléřích (int), aby se nepočítalo s float.
 */
final readonly class OrderItem
{
    public function __construct(
        public string $productName,
        public int $unitPriceInCents,
        public int $quantity,
        public int $unitWeightInGrams,
    ) {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Množství musí být alespoň 1.');
        }
    }

    public function total(): int
    {
        return $this->unitPriceInCents * $this->quantity;
    }

    public function weight(): int
    {
        return $this->unitWeightInGrams * $this->quantity;
    }
}
