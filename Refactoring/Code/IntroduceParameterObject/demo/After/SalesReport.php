<?php

declare(strict_types=1);

namespace After;

/**
 * Tytéž tři metody. Období je jeden parametr a ví o sobě samo.
 */
final class SalesReport
{
    /** @param list<array{paidAt: int, totalInCents: int}> $orders */
    public function __construct(private readonly array $orders)
    {
    }

    public function totalFor(DateRange $period): int
    {
        $sum = 0;

        foreach ($this->orders as $order) {
            if ($period->includes($order['paidAt'])) {
                $sum += $order['totalInCents'];
            }
        }

        return $sum;
    }

    public function countFor(DateRange $period): int
    {
        $count = 0;

        foreach ($this->orders as $order) {
            if ($period->includes($order['paidAt'])) {
                ++$count;
            }
        }

        return $count;
    }

    public function averageFor(DateRange $period): int
    {
        $count = $this->countFor($period);

        if ($count === 0) {
            return 0;
        }

        return (int) round($this->totalFor($period) / $count);
    }

    /** Nová metoda, která by předtím potřebovala počítat dny sama. */
    public function dailyAverageFor(DateRange $period): int
    {
        return (int) round($this->totalFor($period) / $period->days());
    }
}
