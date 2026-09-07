<?php

declare(strict_types=1);

final readonly class Order
{
    public function __construct(
        public string $number,
        public int $totalInCents,
        public int $itemCount,
        public string $customerTier,
        public bool $hasCoupon,
    ) {
    }
}
