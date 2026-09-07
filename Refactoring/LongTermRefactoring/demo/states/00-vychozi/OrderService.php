<?php

declare(strict_types=1);

final class OrderService
{
    public function activeTotal(): int
    {
        $sum = 0;

        foreach (LegacyDb::orders() as $order) {
            if ($order['status'] === 'active') {
                $sum += $order['total'];
            }
        }

        return $sum;
    }
}
