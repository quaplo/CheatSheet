<?php

declare(strict_types=1);

namespace After\Retail;

use After\Core\ComparesByTotal;
use After\Core\Order;

/**
 * PO: modul zná jen abstraktní jádro, ne ostatní moduly.
 */
final class RetailOrder implements Order
{
    use ComparesByTotal;

    public function __construct(
        private readonly string $number,
        private readonly int $totalInCents,
        /** Detail specifický pro maloobchod — v jádru nemá co dělat. */
        private readonly bool $giftWrapped = false,
    ) {
    }

    public function number(): string
    {
        return $this->number;
    }

    public function totalInCents(): int
    {
        return $this->totalInCents;
    }

    public function isGiftWrapped(): bool
    {
        return $this->giftWrapped;
    }
}
