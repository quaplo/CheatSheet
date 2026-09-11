<?php

declare(strict_types=1);

namespace Inverted\Domain;

/** Doména, která nezná nic než sebe. */
final class OrderTotals
{
    public function __construct(private readonly OrderLines $lines)
    {
    }

    public function totalFor(string $orderId, int $customerTier): int
    {
        $total = 0;

        foreach ($this->lines->of($orderId) as $line) {
            $total += $line->priceInCents * $line->quantity;
        }

        return $customerTier <= 2 ? (int) round($total * 0.9) : $total;
    }
}
