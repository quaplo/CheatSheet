<?php

declare(strict_types=1);

namespace After\Subscription;

use After\Core\ComparesByTotal;
use After\Core\Order;

final class SubscriptionOrder implements Order
{
    use ComparesByTotal;

    public function __construct(
        private readonly string $number,
        private readonly int $totalInCents,
        /** Detail specifický pro předplatné. */
        private readonly int $periodInMonths = 12,
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

    public function periodInMonths(): int
    {
        return $this->periodInMonths;
    }
}
