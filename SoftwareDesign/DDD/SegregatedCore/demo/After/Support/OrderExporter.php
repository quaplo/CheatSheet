<?php

declare(strict_types=1);

namespace After\Support;

use After\Core\Order;

final class OrderExporter
{
    public function __construct(private readonly CountryRegistry $countries)
    {
    }

    public function toCsvRow(Order $order, string $countryCode): string
    {
        return implode(';', [
            $order->number,
            $order->status()->value,
            (string) $order->totalInCents(),
            $this->countries->nameOf($countryCode),
        ]);
    }
}
