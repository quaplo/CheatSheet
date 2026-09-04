<?php

declare(strict_types=1);

namespace Before\Subscription;

use Before\Retail\RetailOrder;
use Before\Wholesale\WholesaleOrder;

final class SubscriptionOrder
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

    public function isLargerThanWholesale(WholesaleOrder $other): bool
    {
        return $this->totalInCents > $other->totalInCents();
    }
}
