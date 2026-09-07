<?php

declare(strict_types=1);

namespace Before;

/**
 * Tři metody, tři stejné dvojice parametrů.
 *
 * Pravidlo „spadá do období" je napsané dvakrát (averageFor si ho
 * půjčuje od ostatních) a nikdo si nehlídá, jestli to období vůbec
 * dává smysl.
 */
final class SalesReport
{
    /** @param list<array{paidAt: int, totalInCents: int}> $orders */
    public function __construct(private readonly array $orders)
    {
    }

    public function totalFor(int $fromTs, int $toTs): int
    {
        $sum = 0;

        foreach ($this->orders as $order) {
            if ($order['paidAt'] >= $fromTs && $order['paidAt'] <= $toTs) {
                $sum += $order['totalInCents'];
            }
        }

        return $sum;
    }

    public function countFor(int $fromTs, int $toTs): int
    {
        $count = 0;

        foreach ($this->orders as $order) {
            if ($order['paidAt'] >= $fromTs && $order['paidAt'] <= $toTs) {
                ++$count;
            }
        }

        return $count;
    }

    public function averageFor(int $fromTs, int $toTs): int
    {
        $count = $this->countFor($fromTs, $toTs);

        if ($count === 0) {
            return 0;
        }

        return (int) round($this->totalFor($fromTs, $toTs) / $count);
    }
}
