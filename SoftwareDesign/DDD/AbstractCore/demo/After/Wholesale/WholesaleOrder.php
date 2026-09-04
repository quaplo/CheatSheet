<?php

declare(strict_types=1);

namespace After\Wholesale;

use After\Core\ComparesByTotal;
use After\Core\Order;

final class WholesaleOrder implements Order
{
    use ComparesByTotal;

    public function __construct(
        private readonly string $number,
        private readonly int $totalInCents,
        /** Detail specifický pro velkoobchod. */
        private readonly int $paymentTermInDays = 30,
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

    public function paymentTermInDays(): int
    {
        return $this->paymentTermInDays;
    }
}
