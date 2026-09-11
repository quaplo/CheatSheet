<?php

declare(strict_types=1);

namespace Layered\Ui;

use Layered\Application\ShowOrderTotal;

final class OrderController
{
    public function __construct(private readonly ShowOrderTotal $showOrderTotal)
    {
    }

    public function show(string $orderId, int $customerTier): string
    {
        $total = ($this->showOrderTotal)($orderId, $customerTier);

        return '<strong>' . number_format($total / 100, 2, ',', ' ') . ' Kč</strong>';
    }
}
