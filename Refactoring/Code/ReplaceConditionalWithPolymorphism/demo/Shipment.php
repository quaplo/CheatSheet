<?php

declare(strict_types=1);

final readonly class Shipment
{
    public function __construct(
        public string $orderNumber,
        public int $weightInGrams,
        public int $orderValueInCents,
    ) {
    }
}
