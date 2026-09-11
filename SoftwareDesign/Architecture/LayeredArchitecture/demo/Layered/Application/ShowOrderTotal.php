<?php

declare(strict_types=1);

namespace Layered\Application;

use Layered\Domain\OrderTotals;

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
