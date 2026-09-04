<?php

declare(strict_types=1);

namespace After\Core;

final readonly class OrderItem
{
    public function __construct(
        public string $sku,
        public int $priceInCents,
        public int $quantity,
    ) {
    }

    public function subtotalInCents(): int
    {
        return $this->priceInCents * $this->quantity;
    }
}
