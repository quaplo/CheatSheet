<?php

declare(strict_types=1);

namespace Before\Retail;

use Before\Subscription\SubscriptionOrder;
use Before\Wholesale\WholesaleOrder;

/**
 * PŘED: každý typ objednávky zná ty ostatní.
 *
 * Evans: „either many references will have to be created between
 * modules, which defeats much of the value of the partitioning."
 */
final class RetailOrder
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

    /** Porovnání napříč typy — a tím pádem znalost všech ostatních. */
    public function isLargerThanWholesale(WholesaleOrder $other): bool
    {
        return $this->totalInCents > $other->totalInCents();
    }

    public function isLargerThanSubscription(SubscriptionOrder $other): bool
    {
        return $this->totalInCents > $other->totalInCents();
    }
}
