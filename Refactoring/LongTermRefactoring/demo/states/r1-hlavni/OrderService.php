<?php

declare(strict_types=1);

final class OrderService
{
    public function __construct(private readonly OrderRepository $orders)
    {
    }

    public function activeTotal(): int
    {
        $sum = 0;

        foreach ($this->orders->active() as $order) {
            $sum += $order['total'];
        }

        return $sum;
    }

    public function activeCount(): int
    {
        return count($this->orders->active());
    }
}
