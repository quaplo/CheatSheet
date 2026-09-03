<?php

declare(strict_types=1);

namespace Domain;

final readonly class OrderItem
{
    public function __construct(
        public string $sku,
        public string $productName,
        public Money $unitPrice,
        public int $quantity,
    ) {
    }

    public function total(): Money
    {
        return $this->unitPrice->multipliedBy($this->quantity);
    }
}
