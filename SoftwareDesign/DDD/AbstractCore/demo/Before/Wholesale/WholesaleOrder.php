<?php

declare(strict_types=1);

namespace Before\Wholesale;

use Before\Retail\RetailOrder;
use Before\Subscription\SubscriptionOrder;

final class WholesaleOrder
{
    public function __construct(
        public readonly string $number,
        private readonly int $totalInCents,
    ) {
    }

    public function totalInCents(): int
    {
        return $this->totalInCents;
    }

    public function isLargerThanRetail(RetailOrder $other): bool
    {
        return $this->totalInCents > $other->totalInCents();
    }

    public function isLargerThanSubscription(SubscriptionOrder $other): bool
    {
        return $this->totalInCents > $other->totalInCents();
    }
}
