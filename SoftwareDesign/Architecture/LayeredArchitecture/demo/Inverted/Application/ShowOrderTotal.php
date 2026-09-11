<?php

declare(strict_types=1);

namespace Inverted\Application;

use Inverted\Domain\OrderTotals;

final class ShowOrderTotal
{
    public function __construct(private readonly OrderTotals $totals)
    {
    }

    public function __invoke(string $orderId, int $customerTier): int
    {
        return $this->totals->totalFor($orderId, $customerTier);
    }
}
