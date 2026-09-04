<?php

declare(strict_types=1);

namespace After\Support;

use After\Core\Order;

final class CurrencyConverter
{
    public function toEur(Order $order): float
    {
        return round($order->totalInCents() / 100 / 25.2, 2);
    }
}
