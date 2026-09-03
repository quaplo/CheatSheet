<?php

declare(strict_types=1);

namespace Handlers;

use Domain\OrderPlaced;

/** Čtecí model plněný událostí — viz CQRS. */
final class UpdateSalesStats
{
    public int $orders = 0;
    public int $revenueInCents = 0;

    public function __invoke(OrderPlaced $event): void
    {
        $this->orders++;
        $this->revenueInCents += $event->totalInCents;

        printf("            → statistiky: %d obj., %s Kč\n",
            $this->orders, number_format($this->revenueInCents / 100, 0, ',', ' '));
    }
}
