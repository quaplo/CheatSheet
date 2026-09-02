<?php

declare(strict_types=1);

namespace Domain;

final readonly class OrderItem
{
    public function __construct(
        public string $sku,
        public int $unitPriceInCents,
        public int $quantity,
    ) {
    }

    public function total(): int
    {
        return $this->unitPriceInCents * $this->quantity;
    }
}
